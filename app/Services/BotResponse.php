<?php
/**
 * Created by PhpStorm.
 * User: raphael
 * Date: 15/10/2017
 * Time: 02:36
 */

namespace App\Services;

use App\BotMessage;
use App\Events\PusherDebugEvent;
use App\FinancialTransaction;
use App\Goal;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class BotResponse
{

    /**
     * Build goal render bot response
     *
     * @param Goal $goal
     * @param BotMessage $botMessage
     */
    public static function display_goal_response(Goal $goal, BotMessage &$botMessage)
    {

        if ($goal->type === Goal::TYPE_NOTE){
            $botMessage->buildTextResponse(
                $goal->title . "\r\n" .
                    $goal->notes
            );
        } else if ($goal->type === Goal::TYPE_REMINDER){
            $dueDate = '';
            if ($goal->due_date !== null){
                $date = new Carbon($goal->due_date);
                $date->tz = $botMessage->user->timezone;
                $dueDate = $date . '';
            }

            $botMessage->buildTextResponse(
                $goal->title . " at " . $dueDate
            );
        }

        else {
            $botMessage->buildEventResponse('show_goal', [
                'title' => $goal->title,
                'score' => $goal->score,
                'project' => $goal->project->title
            ]);

        }

    }

    /**
     * Build goal list render bot response
     *
     * @param Collection $goals
     * @param BotMessage $botMessage
     */
    public static function display_goal_list_response(Collection $goals, BotMessage &$botMessage, bool $projectName = false){
        $goalList = [];
        $goals->each(function (Goal $goal, $key) use(&$goalList, $botMessage, $projectName)
        {
            $dueDate = '';
            if ($goal->due_date !== null){
                $date = new Carbon($goal->due_date);
                $date->tz = $botMessage->user->timezone;
                $dueDate = ' at ' . $date;
            }

            $goalList []= "- " . ($projectName ? $goal->project->title . ' :' : '') .  " $goal->title ($goal->score)"
                . $dueDate;
        });

        $text = !empty($goalList) ? implode("\r\n", $goalList) : "No goal found";

        $botMessage->buildTextResponse($text);
    }

    /**
     * Build project list render bot response
     *
     * @param Collection $projects
     * @param BotMessage $botMessage
     */
    public static function display_project_list_response(Collection $projects, BotMessage &$botMessage){
        $projectList = [];
        $projects->each(function ($project, $key) use(&$projectList)
        {
            $projectList []= "- $project->title (" . $project->goals()->whereNull('completed_at')->count() . ")";
        });

        $text = !empty($projectList) ? implode("\r\n", $projectList) : "No project found";

        $botMessage->buildTextResponse($text);
    }

    /**
     * Build fallback bot response
     *
     * @param BotMessage $botMessage
     */
    public static function fallback_response(BotMessage &$botMessage){
        $botMessage->buildTextResponse("Sorry i don't understand...");
    }

    public static function display_expense_response(FinancialTransaction $expense, BotMessage &$botMessage)
    {
        $botMessage->buildTextResponse("New $expense->type \"$expense->title\" of $expense->price $expense->currency saved");
    }

}