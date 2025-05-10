<?php

namespace App\Parsers;

use App\Contexts\AbstractContext;
use App\Contexts\Blade;
use App\Parser\Parse;
use App\Parser\Settings;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Microsoft\PhpParser\Node\Statement\InlineHtml;
use Microsoft\PhpParser\Parser;
use Microsoft\PhpParser\PositionUtilities;
use Microsoft\PhpParser\Range;
use Stillat\BladeParser\Document\Document;
use Stillat\BladeParser\Nodes\BaseNode;
use Stillat\BladeParser\Nodes\Components\ComponentNode;
use Stillat\BladeParser\Nodes\Components\ParameterNode;
use Stillat\BladeParser\Nodes\DirectiveNode;
use Stillat\BladeParser\Nodes\EchoNode;
use Stillat\BladeParser\Nodes\EchoType;
use Stillat\BladeParser\Nodes\LiteralNode;

class InlineHtmlParser extends AbstractParser
{
    protected $echoStrings = [
        '{!!' => '!!}',
        '{{{' => '}}}',
        '{{'  => '}}',
    ];

    protected $startLine = 0;

    /**
     * @var Blade
     */
    protected AbstractContext $context;

    protected array $items = [];

    /**
     * Stillat\BladeParser\Document\Document::fromText treats multibyte characters
     * as indentations and spaces resulting in a miscalculated Node position.
     *
     * This function replaces the multibyte characters with a single, placeholder character
     */
    private function replaceMultibyteChars(string $text, string $placeholder = '*'): string
    {
        return preg_replace('/[^\x00-\x7F]/u', $placeholder, $text);
    }

    public function parse(InlineHtml $node)
    {
        if ($node->getStartPosition() > 0) {
            $range = PositionUtilities::getRangeFromPosition(
                $node->getStartPosition(),
                mb_strlen($node->getText()),
                $node->getRoot()->getFullText(),
            );

            $this->startLine = $range->start->line;
        }

        $this->parseBladeContent(Document::fromText(
            $this->replaceMultibyteChars($node->getText())
        ));

        if (count($this->items)) {
            $blade = new Blade;
            $this->context->initNew($blade);

            $blade->children = $this->items;

            return $blade;
        }

        return $this->context;
    }

    protected function parseBladeContent($node)
    {
        foreach ($node->getNodes() as $child) {
            // TODO: Add other echo types as well
            if ($child instanceof LiteralNode) {
                $this->parseLiteralNode($child);
            }

            if ($child instanceof DirectiveNode) {
                $this->parseBladeDirective($child);
            }

            if ($child instanceof EchoNode) {
                $this->parseEchoNode($child);
            }

            if ($child instanceof ComponentNode) {
                $this->parseComponentNode($child);
            }

            $this->parseBladeContent($child);
        }
    }

    protected function parseComponentNode(ComponentNode $node)
    {
        /** @var Collection<int, ParameterNode> $parameters */
        $parameters = $node->getParameters();

        foreach ($parameters as $parameter) {
            $prefix = Str::match('/^({{{|{{|{!!)/', $parameter->value);

            $snippet = "<?php\n" . str_repeat(' ', $node->getStartIndentationLevel()) . str_replace($prefix, '', $parameter->value) . ';';

            $sourceFile = (new Parser)->parseSourceFile($snippet);

            Settings::$calculatePosition = function (Range $range) use ($node, $parameter) {
                if ($range->start->line === 1) {
                    $rangeCharacters = $range->end->character - $range->start->character;
                    // If component has />, then we need to remove 1 character
                    $selfClosingCharacter = $node->getIsSelfClosing() ? 1 : 0;

                    $firstQuotePosition = strpos($parameter->content, "'");

                    $range->start->character = $parameter->position->startColumn + $firstQuotePosition - $selfClosingCharacter;
                    $range->end->character = $parameter->position->startColumn + $firstQuotePosition + $rangeCharacters - $selfClosingCharacter;
                }

                $range->start->line += $this->startLine + $parameter->position->startLine - 2;
                $range->end->line += $this->startLine + $parameter->position->startLine - 2;

                return $range;
            };

            $result = Parse::parse($sourceFile);

            if (count($result->children) === 0) {
                continue;
            }

            $child = $result->children[0];

            $this->items[] = $child;
        }
    }

    protected function doEchoParse(BaseNode $node, $prefix, $content)
    {
        $snippet = "<?php\n" . str_repeat(' ', $node->getStartIndentationLevel()) . str_replace($prefix, '', $content) . ';';

        $sourceFile = (new Parser)->parseSourceFile($snippet);

        $suffix = $this->echoStrings[$prefix];

        Settings::$calculatePosition = function (Range $range) use ($node, $prefix, $suffix) {
            if ($range->start->line === 1) {
                $range->start->character += mb_strlen($prefix);
                $range->end->character += mb_strlen($suffix);
            }

            $range->start->line += $this->startLine + $node->position->startLine - 2;
            $range->end->line += $this->startLine + $node->position->startLine - 2;

            return $range;
        };

        $result = Parse::parse($sourceFile);

        if (count($result->children) === 0) {
            return;
        }

        $child = $result->children[0];

        $this->items[] = $child;
    }

    protected function parseLiteralNode(LiteralNode $node)
    {
        foreach ($this->echoStrings as $prefix => $suffix) {
            if (!str_starts_with($node->content, $prefix)) {
                continue;
            }

            $this->doEchoParse($node, $prefix, $node->content);
        }
    }

    protected function parseBladeDirective(DirectiveNode $node)
    {
        if ($node->isClosingDirective || !$node->hasArguments()) {
            return;
        }

        $methodUsed = '@' . $node->content;
        $safetyPrefix = 'directive';
        $snippet = "<?php\n" . str_repeat(' ', $node->getStartIndentationLevel()) . str_replace($methodUsed, $safetyPrefix . $node->content, $node->toString() . ';');

        $sourceFile = (new Parser)->parseSourceFile($snippet);

        Settings::$calculatePosition = function (Range $range) use ($node, $safetyPrefix) {
            if ($range->start->line === 1) {
                $range->start->character -= mb_strlen($safetyPrefix) - 1;
                $range->end->character -= mb_strlen($safetyPrefix) - 1;
            }

            $range->start->line += $this->startLine + $node->position->startLine - 2;
            $range->end->line += $this->startLine + $node->position->startLine - 2;

            return $range;
        };

        $result = Parse::parse($sourceFile);

        $child = $result->children[0];

        $child->methodName = '@' . substr($child->methodName, mb_strlen($safetyPrefix));

        $this->items[] = $child;
    }

    protected function parseEchoNode(EchoNode $node)
    {
        $prefix = match ($node->type) {
            EchoType::RawEcho    => '{!!',
            EchoType::TripleEcho => '{{{',
            default              => '{{',
        };

        $this->doEchoParse($node, $prefix, $node->innerContent);
    }
}
