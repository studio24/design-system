<?php

namespace Studio24\DesignSystem\Parser;

use League\CommonMark\Environment\Environment;
use League\CommonMark\Event\DocumentParsedEvent;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\CommonMark\Node\Inline\Link;
use League\CommonMark\Extension\GithubFlavoredMarkdownExtension;
use League\CommonMark\Extension\HeadingPermalink\HeadingPermalinkExtension;
use League\CommonMark\Extension\HeadingPermalink\HeadingPermalinkRenderer;
use League\CommonMark\MarkdownConverter;
use League\CommonMark\Node\Query;

class Markdown
{
    private MarkdownConverter $converter;

    public function __construct()
    {
        // heading-permalinks config
        $config = [
            'heading_permalink' => [
                'html_class' => 'heading-permalink',
                'id_prefix' => '',
                'fragment_prefix' => '',
                'insert' => 'before',
                'min_heading_level' => 2,
                'max_heading_level' => 6,
                'title' => 'Permalink',
                'symbol' => HeadingPermalinkRenderer::DEFAULT_SYMBOL,
            ],
        ];

        $environment = new Environment($config);
        $environment->addExtension(new CommonMarkCoreExtension());

        // @see https://commonmark.thephpleague.com/2.0/extensions/github-flavored-markdown/
        $environment->addExtension(new GithubFlavoredMarkdownExtension());

        // @see https://commonmark.thephpleague.com/2.0/extensions/heading-permalinks/
        $environment->addExtension(new HeadingPermalinkExtension());

        // Process links
        $environment->addEventListener(DocumentParsedEvent::class, [$this, 'linkProcessor']);

        $this->converter = new MarkdownConverter($environment);
    }

    /**
     * Update links to switch local *.md links to *.html
     *
     * @see https://commonmark.thephpleague.com/2.0/customization/event-dispatcher/
     * @param DocumentParsedEvent $event
     */
    public function linkProcessor(DocumentParsedEvent $event)
    {
        $document = $event->getDocument();
        $matchingNodes = (new Query())
            ->where(Query::type(Link::class))
            ->findAll($document);

        /** @var Link $node */
        foreach ($matchingNodes as $node) {

            // Only update if a local URL
            $info = parse_url($node->getUrl());
            if (count($info) > 1 && !isset($info['path'])) {
                continue;
            }
            $node->setUrl(preg_replace('/\.md$/i', '.html', $node->getUrl()));
        }
    }

    /**
     * Return Markdown convertor
     *
     * @return MarkdownConverter
     */
    public function getConvertor(): MarkdownConverter
    {
        return $this->converter;
    }

    /**
     * Render markdown as HTML
     *
     * @param string $content Markdown string
     * @return string Parsed HTML
     */
    public function render(string $content): string
    {
        return $this->getConvertor()->convertToHtml($content);
    }

}