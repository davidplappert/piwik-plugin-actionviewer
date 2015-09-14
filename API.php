<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */
namespace Piwik\Plugins\ActionViewer;

use Piwik\Archive;
use Piwik\DataTable;
use Piwik\DataTable\Row;
use Piwik\Metrics;
use Piwik\Piwik;
use Piwik\Db;
use Piwik\Common;
use Piwik\Period\Range;

/**
 * API for plugin ActionViewer
 *
 * @method static \Piwik\Plugins\ActionViewer\API getInstance()
 */
class API extends \Piwik\Plugin\API
{

    /**
     * Another example method that returns a data table.
     * @param int    $idSite
     * @param string $period
     * @param string $date
     * @param bool|string $segment
     * @return DataTable
     */
    public function getLiveView($idSite, $period, $date, $segment = false)
    {
		$sql = "
			SELECT
				piwik_log_action.name AS 'Event',
				piwik_log_visit.user_id AS 'User',
				piwik_log_link_visit_action.server_time AS 'Time',
				piwik_log_link_visit_action.idlink_va
			FROM piwik_log_link_visit_action
			JOIN piwik_log_action
			ON piwik_log_link_visit_action.idaction_event_action = piwik_log_action.idaction
			JOIN piwik_log_visit
			ON piwik_log_link_visit_action.idvisitor = piwik_log_visit.idvisitor
			WHERE piwik_log_link_visit_action.idsite = ?
			ORDER BY piwik_log_link_visit_action.server_time DESC
			LIMIT 50
		";
        $actionDetails = Db::fetchAll($sql, array($idSite));
		$dataTable = new DataTable();
		foreach ($actionDetails as $action){
			$row = $dataTable->addRow(new Row(array(
				Row::COLUMNS => $action,
				Row::METADATA => $action,
			)));	
			//generate subtable with event custom variables
			
		}
		
		//$dataTable->addRowsFromSimpleArray($actionDetails);
        return $dataTable;
	}
}
