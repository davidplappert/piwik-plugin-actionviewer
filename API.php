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
use Piwik\Plugins\CustomVariables\CustomVariables;

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
    public function getLiveView($idSite, $period, $date, $segment = FALSE)
    {
		$maxCustomVariables = CustomVariables::getNumUsableCustomVariables();
		$cvString = '';
		$i = 1;
		while ($i <= $maxCustomVariables){
			$cvString .= 'piwik_log_link_visit_action.custom_var_k'.$i.', ';
			$cvString .= 'piwik_log_link_visit_action.custom_var_v'.$i.', ';
			$i ++;
		}
		
		$sql = "
			SELECT
				DISTINCT
				piwik_log_action.name AS 'Event',
				piwik_log_visit.user_id AS 'User',
				piwik_log_link_visit_action.server_time,
				".$cvString."
				piwik_log_link_visit_action.idlink_va
			FROM piwik_log_link_visit_action
			JOIN piwik_log_action
			ON piwik_log_link_visit_action.idaction_event_action = piwik_log_action.idaction
			JOIN piwik_log_visit
			ON piwik_log_link_visit_action.idvisitor = piwik_log_visit.idvisitor
			WHERE piwik_log_link_visit_action.idsite = ?
			AND piwik_log_visit.user_id IS NOT NULL
			ORDER BY piwik_log_link_visit_action.server_time DESC
			LIMIT 25
		";
        $actionDetails = Db::fetchAll($sql, array($idSite));
		$dataTable = new DataTable();
		$subDataTable = array();
		foreach ($actionDetails as $action){
			$action['Time'] = $this->prettyTimeAgo($action['server_time']);
			$row = array(
				Row::COLUMNS		=>	$action,
				//Row::METADATA	=>	$action,
			);
			//find and store the custom event(page) vars
			$CVs = array();
			$i = 1;
			while ($i <= $maxCustomVariables){
				if ($action['custom_var_k'.$i] != NULL){ 
					$CVs[$action['custom_var_k'.$i]] = $action['custom_var_v'.$i];
				}
				$i ++;
			}
			if (count($CVs) > 0){
				$subDataTable[$action['idlink_va']] = new DataTable();
				$subDataTable[$action['idlink_va']]->addRowsFromSimpleArray($CVs);
				$row[Row::DATATABLE_ASSOCIATED] = $subDataTable[$action['idlink_va']]->getId();
			}
			$dataTable->addRow(new Row($row));
		}
		
		/*
		echo "<pre>";
		var_dump($dataTable);
		echo "</pre>";
		die();
		*/
		
        return $dataTable;
	}
	
	private function prettyTimeAgo($time)
	{
		//make sure we have epoch time
		$time = 	strtotime($time);
		$timeAgo = time() - $time;
		$timeLimits = array(
			array(60,			'second'),
			array((60*60),		'minute'),
			array((60*60*24),	'hour'),
			array((60*60*24*7),	'day'),
		);
		foreach ($timeLimits as $timeLimitId => $limitInfo){
			if ($timeAgo < $limitInfo[0] && ($timeLimitId == 0 || $timeAgo >= $timeLimits[$timeLimitId-1][0])){
				if ($timeLimitId > 0){
					$prettyTimeAgo = floor($timeAgo / $timeLimits[$timeLimitId-1][0]);
				}else{
					$prettyTimeAgo = $timeAgo;	
				}
				if ($prettyTimeAgo > 1){
					$limitInfo[1] .= 's';
				}
				return $prettyTimeAgo.' '.$limitInfo[1].' ago';
				break;	
			}
		}
	}

    /**
     * Another example method that returns a data table.
     * @param int    $idSite
     * @param string $period
     * @param string $date
     * @param bool|string $segment
     * @return DataTable
     */
    public function getUserList($idSite, $period, $date, $segment = false)
    {	
		$maxCustomVariables = CustomVariables::getNumUsableCustomVariables();
		$cvString = '';
		$i = 1;
		while ($i <= $maxCustomVariables){
			$cvString .= 'piwik_log_visit.custom_var_k'.$i.', ';
			$cvString .= 'piwik_log_visit.custom_var_v'.$i.', ';
			$i ++;
		}
		$sql = "
			SELECT
				piwik_log_visit.visit_last_action_time,
				piwik_log_visit.location_region,
				piwik_log_visit.location_city,
				".$cvString."
				piwik_log_visit.user_id
			FROM piwik_log_visit
			WHERE piwik_log_visit.user_id IS NOT NULL
			AND piwik_log_visit.idsite = ?
			GROUP BY piwik_log_visit.user_id
			ORDER BY piwik_log_visit.visit_last_action_time DESC 
		";
        $actionDetails = Db::fetchAll($sql, array($idSite));
		$dataTable = new DataTable();
		$subDataTable = array();
		foreach ($actionDetails as $action){
			$row = array(
				Row::COLUMNS		=>	$action,
			);
			$dataTable->addRow(new Row($row));
		}
        return $dataTable;
    }

}
