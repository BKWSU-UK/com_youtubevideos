<?php

namespace BKWSU\Component\Youtubevideos\Site\Helper;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Pagination\Pagination;

/**
 * Helper for preparing SEO metadata.
 *
 * @since  1.0.42
 */
class SeoHelper
{
    /**
     * Prepare plain text for use in meta descriptions.
     *
     * @param   string  $text        The source text.
     * @param   int     $maxLength   Maximum length.
     *
     * @return  string
     *
     * @since   1.0.42
     */
    public static function prepareDescription(string $text, int $maxLength = 160): string
    {
        $text = html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/\s+/u', ' ', trim($text)) ?? '';

        if ($text === '' || mb_strlen($text) <= $maxLength) {
            return $text;
        }

        return rtrim(mb_substr($text, 0, $maxLength - 1)) . '…';
    }

    /**
     * Build a page-specific meta description using the title and optional body text.
     *
     * @param   string       $title       The page title.
     * @param   string|null  $body        Optional supporting text.
     * @param   int          $maxLength   Maximum length.
     *
     * @return  string
     *
     * @since   1.0.42
     */
    public static function buildPageDescription(string $title, ?string $body = null, int $maxLength = 160): string
    {
        $title = self::prepareDescription($title, $maxLength);
        $body = self::prepareDescription($body ?? '', $maxLength);

        if ($body === '') {
            return $title;
        }

        return self::prepareDescription($title . '. ' . $body, $maxLength);
    }

    /**
     * Append a pagination suffix when a listing spans multiple pages.
     *
     * @param   string            $description  The base description.
     * @param   Pagination|null   $pagination   The pagination object.
     * @param   int               $maxLength    Maximum length.
     *
     * @return  string
     *
     * @since   1.0.42
     */
    public static function appendPaginationSuffix(string $text, ?Pagination $pagination, ?int $maxLength = 160): string
    {
        if ($pagination === null || $pagination->pagesTotal <= 1) {
            return $maxLength === null ? $text : self::prepareDescription($text, $maxLength);
        }

        $suffix = Text::sprintf(
            'COM_YOUTUBEVIDEOS_META_DESCRIPTION_PAGE',
            $pagination->pagesCurrent,
            $pagination->pagesTotal
        );

        $combined = $text . $suffix;

        return $maxLength === null ? $combined : self::prepareDescription($combined, $maxLength);
    }

    /**
     * Append a pagination suffix to a page title without truncating it.
     *
     * @param   string            $title       The page title.
     * @param   Pagination|null   $pagination  The pagination object.
     *
     * @return  string
     *
     * @since   1.0.43
     */
    public static function appendPaginationTitle(string $title, ?Pagination $pagination): string
    {
        return self::appendPaginationSuffix($title, $pagination, null);
    }
}
