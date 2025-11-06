<?php
namespace BKWSU\Component\Youtubevideos\Administrator\Table;

use Joomla\CMS\Table\Table;
use Joomla\Database\DatabaseDriver;

/**
 * OAuth Token Table class
 *
 * @since  1.0.2
 */
class OauthTokenTable extends Table
{
    /**
     * Constructor
     *
     * @param   DatabaseDriver  $db  Database connector object
     *
     * @since   1.0.2
     */
    public function __construct(DatabaseDriver $db)
    {
        parent::__construct('#__youtubevideos_oauth_tokens', 'id', $db);
    }
}


