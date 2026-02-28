<?php

require_once dirname(__FILE__) . '/Vo/AmfResponse.php';
require_once dirname(__FILE__) . '/Vo/UserActionDailyVO.php';

class amfActionService
{
    public function getLastDoneActionToday($playerId, $action, $timeBetweenUse)
    {
        if (!Panfu::isLoggedIn()) {
            return;
        }

        try {
            $pdo = Database::getConnection();

            // Current timestamp in milliseconds (Panfu standard)
            $timestamp = floor(microtime(true) * 1000);

            $stmt = $pdo->prepare(
                "SELECT timestamp, done_times 
                 FROM actions 
                 WHERE player_id = ? AND action = ?"
            );
            $stmt->execute([$playerId, $action]);

            $doneToday = 0;
            $doneTimes = 0;
            $lastDoneActionTime = 0;

            if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {

                // Time passed since last action
                $lastDoneActionTime = $timestamp - (int)$row['timestamp'];
                $doneTimes = (int)$row['done_times'];

                if ($lastDoneActionTime <= $timeBetweenUse) {
                    // Still within cooldown window
                    $doneToday = 1;
                    $doneTimes++;

                    $update = $pdo->prepare(
                        "UPDATE actions 
                         SET timestamp = ?, done_times = ?
                         WHERE player_id = ? AND action = ?"
                    );
                    $update->execute([
                        $timestamp,
                        $doneTimes,
                        $playerId,
                        $action
                    ]);
                } else {
                    // Cooldown expired → reset counter
                    $doneTimes = 0;

                    $update = $pdo->prepare(
                        "UPDATE actions 
                         SET timestamp = ?, done_times = 0
                         WHERE player_id = ? AND action = ?"
                    );
                    $update->execute([
                        $timestamp,
                        $playerId,
                        $action
                    ]);
                }

            } else {
                // First time action is performed
                $insert = $pdo->prepare(
                    "INSERT INTO actions (player_id, action, timestamp, done_times)
                     VALUES (?, ?, ?, 0)"
                );
                $insert->execute([
                    $playerId,
                    $action,
                    $timestamp
                ]);
            }

            // Build VO exactly how the client expects
            $vo = new UserActionDailyVO();
            $vo->playerId = $playerId;
            $vo->actionId = $action;
            $vo->doneToday = $doneToday;
            $vo->time = $timestamp;
            $vo->doneInTime = $doneTimes;
            $vo->lastDoneActionTime = $lastDoneActionTime;

            $response = new AmfResponse();
            $response->statusCode = (strpos($action, 'master') !== false) ? 1 : 0;
            $response->message = $action;
            $response->valueObject = $vo;

            return $response;

        } catch (PDOException $e) {
            throw new Exception(
                'amfActionService::getLastDoneActionToday error: ' . $e->getMessage()
            );
        }
    }

    public function performAction($playerId, $action)
    {
        // Panfu.me does almost nothing here for daily actions
        switch ($action) {
            case 'played10':
                // Handled by getLastDoneActionToday
                break;

            default:
                $result = new AmfResponse();
                $result->statusCode = 0;
                $result->message = $action;
                $result->valueObject = null;
                return $result;
        }
    }
}
