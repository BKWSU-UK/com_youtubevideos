<?php
namespace BKWSU\Component\Youtubevideos\Administrator\Table;

use Joomla\CMS\Application\ApplicationHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Versioning\VersionableTableInterface;
use Joomla\Database\DatabaseDriver;
use Joomla\Event\DispatcherInterface;

/**
 * Featured video table
 *
 * @since  1.0.0
 */
class FeaturedTable extends Table implements VersionableTableInterface
{
    /**
     * Constructor
     *
     * @param   DatabaseDriver        $db          Database connector object
     * @param   ?DispatcherInterface  $dispatcher  Event dispatcher for this table
     *
     * @since   1.0.0
     */
    public function __construct(DatabaseDriver $db, DispatcherInterface $dispatcher = null)
    {
        $this->typeAlias = 'com_youtubevideos.featured';

        parent::__construct('#__youtubevideos_featured', 'id', $db, $dispatcher);

        $this->setColumnAlias('published', 'published');
    }

    /**
     * Method to perform sanity checks on the Table instance properties to
     * ensure they are safe to store in the database.
     *
     * @return  boolean  True if the instance is sane and able to be stored in the database.
     *
     * @since   1.0.0
     */
    public function check()
    {
        try {
            parent::check();
        } catch (\Exception $e) {
            $this->setError($e->getMessage());
            return false;
        }

        // Check for valid title
        if (trim($this->title) == '') {
            $this->setError(Text::_('COM_YOUTUBEVIDEOS_WARNING_PROVIDE_VALID_TITLE'));
            return false;
        }

        // Check for valid youtube_video_id
        if (trim($this->youtube_video_id) == '') {
            $this->setError(Text::_('COM_YOUTUBEVIDEOS_WARNING_PROVIDE_VALID_VIDEO_ID'));
            return false;
        }

        // Convert empty string to NULL for nullable integer fields
        if (isset($this->category_id) && $this->category_id === '') {
            $this->category_id = null;
        }

        if (isset($this->playlist_id) && $this->playlist_id === '') {
            $this->playlist_id = null;
        }

        // Convert empty string to NULL for nullable datetime fields
        if (isset($this->publish_up) && $this->publish_up === '') {
            $this->publish_up = null;
        }

        if (isset($this->publish_down) && $this->publish_down === '') {
            $this->publish_down = null;
        }

        // Generate alias if empty
        if (empty($this->alias)) {
            $this->alias = $this->title;
        }

        $this->alias = ApplicationHelper::stringURLSafe($this->alias);

        if (trim(str_replace('-', '', $this->alias)) == '') {
            $this->alias = Factory::getDate()->format('Y-m-d-H-i-s');
        }

        // Set created date if new record
        if (!(int) $this->created) {
            $this->created = Factory::getDate()->toSql();
        }

        // Set created_by if new record
        if (empty($this->created_by)) {
            $this->created_by = Factory::getApplication()->getIdentity()->id;
        }

        // Set modified date
        $this->modified = Factory::getDate()->toSql();

        // Set modified_by
        if (!empty(Factory::getApplication()->getIdentity()->id)) {
            $this->modified_by = Factory::getApplication()->getIdentity()->id;
        }

        return true;
    }

    /**
     * Method to store a row in the database from the Table instance properties.
     *
     * If a primary key value is set the row with that primary key value will be updated with the instance property values.
     * If no primary key value is set a new row will be inserted into the database with properties from the Table instance.
     *
     * @param   boolean  $updateNulls  True to update fields even if they are null.
     *
     * @return  boolean  True on success.
     *
     * @since   1.0.0
     */
    public function store($updateNulls = true)
    {
        return parent::store($updateNulls);
    }

    /**
     * Get the type alias for UCM features
     *
     * @return  string  The alias as described above
     *
     * @since   1.0.0
     */
    public function getTypeAlias()
    {
        return $this->typeAlias;
    }
}

