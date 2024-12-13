<?php

namespace App\Parsers;

use App\Contexts\AbstractContext;
use App\Contexts\Argument;
use App\Contexts\Blade;
use App\Parser\Parse;
use App\Parser\Settings;
use Illuminate\Support\Str;
use Microsoft\PhpParser\Node\Statement\InlineHtml;
use Microsoft\PhpParser\Parser;
use Microsoft\PhpParser\Range;
use Stillat\BladeParser\Document\Document;
use Stillat\BladeParser\Nodes\Components\ComponentNode;
use Stillat\BladeParser\Nodes\DirectiveNode;
use Stillat\BladeParser\Nodes\EchoNode;
use Stillat\BladeParser\Nodes\EchoType;

class InlineHtmlParser extends AbstractParser
{
    /**
     * @var Blade
     */
    protected AbstractContext $context;

    protected array $items = [];

    public function parse(InlineHtml $node)
    {
        $this->parseBladeContent(Document::fromText($node->getText()));

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
            if ($child instanceof DirectiveNode) {
                $this->parseBladeDirective($child);
            }

            if ($child instanceof EchoNode) {
                $this->parseEchoNode($child);
            }

            $this->parseBladeContent($child);
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
                $range->start->character -= strlen($safetyPrefix) - 1;
                $range->end->character -= strlen($safetyPrefix) - 1;
            }

            $range->start->line += $node->position->startLine - 2;
            $range->end->line += $node->position->startLine - 2;

            return $range;
        };

        $result = Parse::parse($sourceFile);

        $child = $result->children[0];

        $child->methodName = '@' . substr($child->methodName, strlen($safetyPrefix));

        $this->items[] = $child;
    }

    protected function parseEchoNode(EchoNode $node)
    {
        $snippet = "<?php\n" . str_repeat(' ', $node->getStartIndentationLevel()) . $node->innerContent . ';';

        $sourceFile = (new Parser)->parseSourceFile($snippet);

        Settings::$calculatePosition = function (Range $range) use ($node) {
            $prefix = match ($node->type) {
                EchoType::RawEcho => '{!!',
                EchoType::TripleEcho => '{{{',
                default => '{{',
            };

            $suffix = match ($node->type) {
                EchoType::RawEcho => '!!}',
                EchoType::TripleEcho => '}}}',
                default => '}}',
            };

            if ($range->start->line === 1) {
                $range->start->character += strlen($prefix);
                $range->end->character += strlen($suffix);
            }

            $range->start->line += $node->position->startLine - 2;
            $range->end->line += $node->position->startLine - 2;

            return $range;
        };

        $result = Parse::parse($sourceFile);

        if (count($result->children) === 0) {
            return;
        }

        $child = $result->children[0];

        $this->items[] = $child;
    }
}
