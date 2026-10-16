<?php
namespace BKWSU\Component\Youtubevideos\Administrator\Table;

use Joomla\CMS\Application\ApplicationHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\Database\ParameterType;
use Joomla\String\StringHelper;

/**
 * Shared alias handling for the component tables.
 *
 * New records get a unique alias by incrementing a numeric suffix (alias, alias-2, alias-3...).
 * Existing records keep the alias the editor chose, so a clash is reported as an error.
 *
 * @since  1.0.45
 */
trait UniqueAliasTrait
{
    /**
     * Normalise the alias and make sure it is unique within the table.
     *
     * @return  boolean  True when the alias is valid, false (with an error set) otherwise.
     *
     * @since   1.0.45
     */
    protected function prepareUniqueAlias(): bool
    {
        if (empty($this->alias)) {
            $this->alias = $this->title;
        }

        $this->alias = ApplicationHelper::stringURLSafe($this->alias);

        if (trim(str_replace('-', '', $this->alias)) === '') {
            $this->alias = Factory::getDate()->format('Y-m-d-H-i-s');
        }

        if (!$this->aliasExists($this->alias, (int) $this->id)) {
            return true;
        }

        if ((int) $this->id) {
            // Leave pre-existing duplicates alone (changing them would change public URLs);
            // only reject an alias the editor has just changed into a clash.
            if ($this->alias === $this->loadStoredAlias((int) $this->id)) {
                return true;
            }

            $this->setError(Text::_('COM_YOUTUBEVIDEOS_ERROR_UNIQUE_ALIAS'));

            return false;
        }

        do {
            $this->alias = StringHelper::increment($this->alias, 'dash');
        } while ($this->aliasExists($this->alias, 0));

        return true;
    }

    /**
     * Check whether an alias is already used by another record.
     *
     * @param   string   $alias      The alias to check.
     * @param   integer  $excludeId  A record id to ignore (the current record).
     *
     * @return  boolean
     *
     * @since   1.0.45
     */
    protected function aliasExists(string $alias, int $excludeId = 0): bool
    {
        $db    = $this->_db;
        $query = $db->getQuery(true)
            ->select($db->quoteName($this->_tbl_key))
            ->from($db->quoteName($this->_tbl))
            ->where($db->quoteName('alias') . ' = :alias')
            ->bind(':alias', $alias);

        if ($excludeId > 0) {
            $query->where($db->quoteName($this->_tbl_key) . ' != :excludeId')
                ->bind(':excludeId', $excludeId, ParameterType::INTEGER);
        }

        $db->setQuery($query, 0, 1);

        return (bool) $db->loadResult();
    }

    /**
     * Load the alias currently stored for a record.
     *
     * @param   integer  $id  The record id.
     *
     * @return  string|null
     *
     * @since   1.0.45
     */
    private function loadStoredAlias(int $id): ?string
    {
        $db    = $this->_db;
        $query = $db->getQuery(true)
            ->select($db->quoteName('alias'))
            ->from($db->quoteName($this->_tbl))
            ->where($db->quoteName($this->_tbl_key) . ' = :id')
            ->bind(':id', $id, ParameterType::INTEGER);

        $result = $db->setQuery($query)->loadResult();

        return $result === null ? null : (string) $result;
    }
}
