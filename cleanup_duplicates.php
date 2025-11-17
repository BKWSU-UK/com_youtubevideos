<?php
/**
 * Cleanup script for duplicate YouTube video entries
 * 
 * This script identifies and optionally removes duplicate entries in the
 * #__youtubevideos_featured table where multiple records share the same youtube_video_id.
 * 
 * USAGE:
 * 1. Upload this file to your Joomla root directory
 * 2. Run from command line: php cleanup_duplicates.php
 * 3. Or access via browser: https://yoursite.com/cleanup_duplicates.php
 * 
 * SAFETY:
 * - By default, runs in DRY RUN mode (reports only, no deletions)
 * - To actually delete duplicates, set $dryRun = false
 * - Always backup your database before running in deletion mode
 * 
 * @package     YouTube Videos Component
 * @since       1.0.2
 */

// Set to false to actually delete duplicates (BACKUP YOUR DATABASE FIRST!)
$dryRun = true;

// Safety check - only allow execution in CLI or by logged-in Super Users
define('_JEXEC', 1);
define('JPATH_BASE', __DIR__);

require_once JPATH_BASE . '/includes/defines.php';
require_once JPATH_BASE . '/includes/framework.php';

use Joomla\CMS\Factory;

// Check if running in CLI or if user is Super User
$app = Factory::getApplication();
$isCli = $app->isClient('cli');
$user = $app->getIdentity();

if (!$isCli && (!$user || !$user->authorise('core.admin'))) {
    die('Access Denied: This script can only be run by Super Users or via command line.');
}

echo "YouTube Videos - Duplicate Cleanup Script\n";
echo str_repeat('=', 60) . "\n";
echo "Mode: " . ($dryRun ? "DRY RUN (no changes will be made)" : "LIVE (duplicates will be deleted)") . "\n";
echo str_repeat('=', 60) . "\n\n";

try {
    $db = Factory::getDbo();
    
    // Find all duplicate youtube_video_ids
    $query = $db->getQuery(true)
        ->select([
            $db->quoteName('youtube_video_id'),
            'COUNT(*) as count',
            'GROUP_CONCAT(' . $db->quoteName('id') . ' ORDER BY ' . $db->quoteName('id') . ') as ids',
            'SUM(CASE WHEN published = 1 THEN 1 ELSE 0 END) as published_count',
            'SUM(CASE WHEN published = 0 THEN 1 ELSE 0 END) as unpublished_count'
        ])
        ->from($db->quoteName('#__youtubevideos_featured'))
        ->group($db->quoteName('youtube_video_id'))
        ->having('COUNT(*) > 1');
    
    $db->setQuery($query);
    $duplicates = $db->loadObjectList();
    
    if (empty($duplicates)) {
        echo "✓ No duplicate entries found. Your database is clean!\n\n";
        exit(0);
    }
    
    echo "Found " . count($duplicates) . " YouTube video(s) with duplicate entries:\n\n";
    
    $totalDuplicates = 0;
    $totalDeleted = 0;
    
    foreach ($duplicates as $duplicate) {
        $ids = explode(',', $duplicate->ids);
        $duplicateCount = count($ids) - 1; // Keep one, rest are duplicates
        $totalDuplicates += $duplicateCount;
        
        echo "Video ID: {$duplicate->youtube_video_id}\n";
        echo "  - Total entries: " . count($ids) . "\n";
        echo "  - Published: {$duplicate->published_count}, Unpublished: {$duplicate->unpublished_count}\n";
        echo "  - Database IDs: " . implode(', ', $ids) . "\n";
        
        // Strategy: Keep the first (oldest) entry, delete the rest
        $keepId = $ids[0];
        $deleteIds = array_slice($ids, 1);
        
        echo "  - Will keep ID: {$keepId}\n";
        echo "  - Will delete IDs: " . implode(', ', $deleteIds) . "\n";
        
        if (!$dryRun) {
            // Delete duplicate entries
            $query = $db->getQuery(true)
                ->delete($db->quoteName('#__youtubevideos_featured'))
                ->where($db->quoteName('id') . ' IN (' . implode(',', array_map('intval', $deleteIds)) . ')');
            
            $db->setQuery($query);
            $db->execute();
            $affected = $db->getAffectedRows();
            $totalDeleted += $affected;
            
            echo "  ✓ Deleted {$affected} duplicate(s)\n";
        } else {
            echo "  ℹ DRY RUN: Would delete " . count($deleteIds) . " duplicate(s)\n";
        }
        
        echo "\n";
    }
    
    echo str_repeat('-', 60) . "\n";
    echo "Summary:\n";
    echo "  - Duplicate sets found: " . count($duplicates) . "\n";
    echo "  - Total duplicate entries: {$totalDuplicates}\n";
    
    if (!$dryRun) {
        echo "  - Entries deleted: {$totalDeleted}\n";
        echo "\n✓ Cleanup completed successfully!\n";
    } else {
        echo "\n⚠ DRY RUN MODE - No changes were made.\n";
        echo "To actually delete duplicates:\n";
        echo "  1. Backup your database\n";
        echo "  2. Edit this script and set \$dryRun = false\n";
        echo "  3. Run the script again\n";
    }
    
    echo "\n";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

// Add recommendation to add unique constraint
echo "\nRECOMMENDATION:\n";
echo "To prevent future duplicates, consider adding a unique constraint:\n";
echo "ALTER TABLE `#__youtubevideos_featured` \n";
echo "  ADD UNIQUE INDEX `idx_youtube_video_id_unique` (`youtube_video_id`);\n";
echo "\n";

// Remind user to delete this script
if (!$isCli) {
    echo "⚠ SECURITY: Please delete this script after use!\n";
}


