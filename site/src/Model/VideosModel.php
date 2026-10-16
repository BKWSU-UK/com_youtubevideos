<?php

namespace BKWSU\Component\Youtubevideos\Site\Model;

\defined('_JEXEC') or die;

use BKWSU\Component\Youtubevideos\Site\Helper\VideoHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\Database\ParameterType;

/**
 * Videos List Model
 *
 * @since  1.0.0
 */
class VideosModel extends ListModel
{
    /**
     * Model context string.
     *
     * @var    string
     * @since  1.0.0
     */
    protected $context = 'com_youtubevideos.videos';

    /**
     * Cached category options for the filter dropdown.
     *
     * @var    array|null
     * @since  1.0.0
     */
    protected ?array $categoryOptions = null;

    /**
     * Constructor.
     *
     * @param   array  $config  An optional associative array of configuration settings.
     *
     * @since   1.0.0
     */
    public function __construct($config = [])
    {
        if (empty($config['filter_fields'])) {
            $config['filter_fields'] = [
                'search',
                'tag',
                'category_id',
            ];
        }

        parent::__construct($config);
    }

    /**
     * Method to auto-populate the model state.
     *
     * @param   string  $ordering   An optional ordering field.
     * @param   string  $direction  An optional direction (asc|desc).
     *
     * @return  void
     *
     * @since   1.0.0
     */
    protected function populateState($ordering = null, $direction = null): void
    {
        $app   = Factory::getApplication();
        $input = $app->getInput();

        // Keep filter state separate for every menu item.
        $this->context = 'com_youtubevideos.videos.' . $input->getInt('Itemid', 0);

        $params = $app->getParams();
        $this->setState('params', $params);

        // Let the parent set up list state first; the filter and paging values below override it.
        parent::populateState($ordering, $direction);

        // Request variables set by the menu item
        $playlistId = $input->getInt('playlist_id', 0);
        $this->setState('playlist_id', $playlistId);

        $categoryId = $input->getInt('category_id', 0);
        $this->setState('category_id', $categoryId);

        $filtersInput = $input->get('filter', null, 'array');

        if ($filtersInput !== null) {
            $search         = trim((string) ($filtersInput['search'] ?? ''));
            $tagFilter      = (int) ($filtersInput['tag'] ?? 0);
            $categoryFilter = (int) ($filtersInput['category_id'] ?? 0);

            $app->setUserState($this->context . '.filter.search', $search);
            $app->setUserState($this->context . '.filter.tag', $tagFilter);
            $app->setUserState($this->context . '.filter.category_id', $categoryFilter);
            $app->setUserState($this->context . '.filter.category_submitted', true);
            $categorySubmitted = true;
        } else {
            $search            = (string) $app->getUserState($this->context . '.filter.search', '');
            $tagFilter         = (int) $app->getUserState($this->context . '.filter.tag', 0);
            $categoryFilter    = (int) $app->getUserState($this->context . '.filter.category_id', 0);
            $categorySubmitted = (bool) $app->getUserState($this->context . '.filter.category_submitted', false);
        }

        $this->setState('filter.search', $search);
        $this->setState('filter.tag', (int) $params->get('show_tag_filter', 1) ? $tagFilter : 0);

        if ($categorySubmitted) {
            $this->setState('filter.category_id', $categoryFilter);
        } elseif ($categoryId > 0) {
            $this->setState('filter.category_id', $categoryId);
        } else {
            // Use default category from params if category filter is shown and no filter submitted
            $showCategoryFilter = (int) $params->get('show_category_filter', 1);
            $defaultCategoryId  = $showCategoryFilter ? (int) $params->get('default_category_id', 0) : 0;
            $this->setState('filter.category_id', $defaultCategoryId);
        }

        // The page size is always a multiple of the number of grid columns.
        $videosPerRow = VideoHelper::columns($params);
        $defaultLimit = $this->getDefaultLimit($params, $videosPerRow);
        $limit        = (int) $app->getUserStateFromRequest($this->context . '.list.limit', 'limit', $defaultLimit, 'uint');

        if ($limit > 0) {
            $limit = (int) (ceil($limit / $videosPerRow) * $videosPerRow);
        }

        $this->setState('list.limit', $limit);

        // Accept both limitstart and start (the SEF router rewrites one to the other).
        $limitstart = $input->getInt('limitstart', -1);

        if ($limitstart < 0) {
            $limitstart = $input->getUint('start', 0);
        }

        $this->setState('list.start', $limitstart);
    }

    /**
     * Default page size: the configured videos per page rounded up to whole rows,
     * or four rows when not configured.
     *
     * @param   \Joomla\Registry\Registry  $params        Merged parameters.
     * @param   integer                    $videosPerRow  Grid columns.
     *
     * @return  integer
     *
     * @since   1.0.45
     */
    private function getDefaultLimit($params, int $videosPerRow): int
    {
        $perPage = (int) $params->get('videos_per_page', 0);

        if ($perPage <= 0) {
            return $videosPerRow * 4;
        }

        return (int) (ceil($perPage / $videosPerRow) * $videosPerRow);
    }

    /**
     * Method to get the data that should be injected in the form.
     *
     * @return  array  The data for the form.
     *
     * @since   1.0.0
     */
    protected function loadFormData(): array
    {
        return [
            'filter' => [
                'search'      => $this->getState('filter.search', ''),
                'category_id' => $this->getState('filter.category_id', 0),
                'tag'         => $this->getState('filter.tag', 0),
            ],
            'list' => [
                'limit' => $this->getState('list.limit', 12),
            ],
        ];
    }

    /**
     * Method to get the filter form.
     *
     * @param   array    $data      Data to bind to the form.
     * @param   boolean  $loadData  True if the form is to load its own data (default case), false if not.
     *
     * @return  \Joomla\CMS\Form\Form|false  A Form object on success, false on failure
     *
     * @since   1.0.0
     */
    public function getFilterForm($data = [], $loadData = true)
    {
        $form = $this->loadForm(
            $this->context . '.filter',
            'filter_videos',
            [
                'control'   => '',
                'load_data' => $loadData,
            ]
        );

        if (!$form) {
            return false;
        }

        $params       = $this->getState('params');
        $videosPerRow = VideoHelper::columns($params);
        $defaultLimit = $this->getDefaultLimit($params, $videosPerRow);
        $limits       = array_map(static fn (int $rows): int => $videosPerRow * $rows, [1, 2, 3, 4, 6, 8, 12, 16]);
        $limits[]     = $defaultLimit;
        $limits[]     = (int) $this->getState('list.limit', $defaultLimit);
        $limits       = array_filter(array_unique($limits));
        sort($limits);

        $limitField = new \SimpleXMLElement('<field name="limit" type="list" onchange="this.form.submit();" class="form-select list-limit"/>');
        $limitField->addAttribute('label', 'JGLOBAL_LIST_LIMIT');
        $limitField->addAttribute('default', (string) $defaultLimit);

        foreach ($limits as $value) {
            $limitField->addChild('option', (string) $value)->addAttribute('value', (string) $value);
        }

        $form->setField($limitField, 'list');

        $this->setListField(
            $form,
            'category_id',
            (int) $params->get('show_category_filter', 1) ? $this->getCategoryOptions() : [],
            'COM_YOUTUBEVIDEOS_FILTER_CATEGORY_OPTION_ALL'
        );

        $this->setListField(
            $form,
            'tag',
            (int) $params->get('show_tag_filter', 1) ? $this->getTagOptions() : [],
            'COM_YOUTUBEVIDEOS_SELECT_TAG'
        );

        return $form;
    }

    /**
     * Fill a filter list field with options, or remove it when there are none.
     *
     * @param   \Joomla\CMS\Form\Form  $form      The filter form.
     * @param   string                  $name      Field name in the filter group.
     * @param   array                   $options   Objects with id and title.
     * @param   string                  $emptyKey  Language key for the "all" option.
     *
     * @return  void
     *
     * @since   1.0.45
     */
    private function setListField($form, string $name, array $options, string $emptyKey): void
    {
        if (empty($options)) {
            $form->removeField($name, 'filter');

            return;
        }

        $label = $name === 'tag' ? 'COM_YOUTUBEVIDEOS_FILTER_TAG_LABEL' : 'COM_YOUTUBEVIDEOS_FILTER_CATEGORY_LABEL';
        $field = new \SimpleXMLElement('<field type="list" class="form-select" onchange="this.form.submit();"/>');
        $field->addAttribute('name', $name);
        $field->addAttribute('label', $label);
        $field->addChild('option', htmlspecialchars(Text::_($emptyKey), ENT_QUOTES, 'UTF-8'))->addAttribute('value', '');

        foreach ($options as $option) {
            $field->addChild('option', htmlspecialchars((string) $option->title, ENT_QUOTES, 'UTF-8'))
                ->addAttribute('value', (string) (int) $option->id);
        }

        $form->setField($field, 'filter');
    }

    /**
     * Method to get a list query for videos from database
     *
     * @return  \Joomla\Database\DatabaseQuery  A database query
     *
     * @since   1.0.0
     */
    protected function getListQuery()
    {
        $db    = $this->getDatabase();
        $query = $db->getQuery(true);

        $query->select('v.*')
            ->select($db->quoteName(['s.views', 's.likes'], ['views', 'likes']))
            ->from($db->quoteName('#__youtubevideos_featured', 'v'))
            ->leftJoin(
                $db->quoteName('#__youtubevideos_statistics', 's'),
                $db->quoteName('s.youtube_video_id') . ' = ' . $db->quoteName('v.youtube_video_id')
            );

        VideoHelper::applyVisibility($query, $db, 'v');
        VideoHelper::applyCategoryVisibility($query, $db, 'v', 'c');

        // Filter by category (filter selection overrides request parameter)
        $categoryId = (int) $this->getState('filter.category_id', 0) ?: (int) $this->getState('category_id', 0);

        if ($categoryId > 0) {
            $query->where($db->quoteName('v.category_id') . ' = :category_id')
                ->bind(':category_id', $categoryId, ParameterType::INTEGER);
        }

        // Filter by playlist if set in menu item
        $playlistId = (int) $this->getState('playlist_id', 0);

        if ($playlistId > 0) {
            $query->where($db->quoteName('v.playlist_id') . ' = :playlist_id')
                ->bind(':playlist_id', $playlistId, ParameterType::INTEGER);
        }

        // Filter by tag
        $tagId = (int) $this->getState('filter.tag', 0);

        if ($tagId > 0) {
            $query->join(
                'INNER',
                $db->quoteName('#__youtubevideos_video_tag_map', 'vtm'),
                $db->quoteName('vtm.video_id') . ' = ' . $db->quoteName('v.youtube_video_id')
            )
                ->where($db->quoteName('vtm.tag_id') . ' = :tag_id')
                ->bind(':tag_id', $tagId, ParameterType::INTEGER);
        }

        // Filter by search
        $search = trim((string) $this->getState('filter.search', ''));

        if ($search !== '') {
            $pattern = VideoHelper::likePattern($search);
            $query->where(
                '(' . $db->quoteName('v.title') . ' LIKE :search_title OR '
                . $db->quoteName('v.description') . ' LIKE :search_desc)'
            )
                ->bind(':search_title', $pattern)
                ->bind(':search_desc', $pattern);
        }

        $query->order($db->quoteName('v.ordering') . ' ASC')
            ->order($db->quoteName('v.created') . ' DESC');

        return $query;
    }

    /**
     * Method to get videos from database
     *
     * @return  array  Array of normalised video objects
     *
     * @since   1.0.0
     */
    public function getVideos(): array
    {
        return VideoHelper::normaliseList($this->getItems() ?: []);
    }

    /**
     * Retrieve published categories available to the current user.
     *
     * @return  array
     *
     * @since   1.0.0
     */
    protected function getCategoryOptions(): array
    {
        if ($this->categoryOptions !== null) {
            return $this->categoryOptions;
        }

        $db    = $this->getDatabase();
        $query = $db->getQuery(true)
            ->select($db->quoteName(['c.id', 'c.title']))
            ->from($db->quoteName('#__youtubevideos_categories', 'c'))
            ->order($db->quoteName('c.ordering') . ' ASC')
            ->order($db->quoteName('c.title') . ' ASC');

        VideoHelper::applyVisibility($query, $db, 'c', false);

        $this->categoryOptions = $db->setQuery($query)->loadObjectList() ?: [];

        return $this->categoryOptions;
    }

    /**
     * Retrieve published tags that are attached to at least one video.
     *
     * @return  array
     *
     * @since   1.0.45
     */
    protected function getTagOptions(): array
    {
        $db    = $this->getDatabase();
        $query = $db->getQuery(true)
            ->select('DISTINCT ' . $db->quoteName('t.id') . ', ' . $db->quoteName('t.title'))
            ->from($db->quoteName('#__youtubevideos_tags', 't'))
            ->join(
                'INNER',
                $db->quoteName('#__youtubevideos_video_tag_map', 'm'),
                $db->quoteName('m.tag_id') . ' = ' . $db->quoteName('t.id')
            )
            ->where($db->quoteName('t.published') . ' = 1')
            ->order($db->quoteName('t.title') . ' ASC');

        try {
            return $db->setQuery($query)->loadObjectList() ?: [];
        } catch (\RuntimeException $e) {
            return [];
        }
    }
}
