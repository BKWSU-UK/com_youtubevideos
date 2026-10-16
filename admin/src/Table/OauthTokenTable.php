<?php
namespace BKWSU\Component\Youtubevideos\Administrator\Table;

use Joomla\CMS\Table\Table;
use Joomla\Database\DatabaseDriver;
use Joomla\Event\DispatcherInterface;

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
     * @param   DatabaseDriver        $db          Database connector object
     * @param   ?DispatcherInterface  $dispatcher  Event dispatcher for this table
     *
     * @since   1.0.2
     */
    public function __construct(DatabaseDriver $db, ?DispatcherInterface $dispatcher = null)
    {
        parent::__construct('#__youtubevideos_oauth_tokens', 'id', $db, $dispatcher);
    }
}





