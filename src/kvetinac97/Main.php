<?php

// EasyQUIZ plugin v2
// © kvetinac97 2016

namespace kvetinac97;

use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\TextFormat;
use pocketmine\event\Listener;
use pocketmine\utils\Config;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\ConsoleCommandSender;

class Main extends PluginBase implements Listener{

    /** @var Config $cfg */
    public $cfg;
    /** @var Config $msg */
    public $msg;
    public $data;

    public function onEnable(){
        $this->getServer()->getPluginManager()->registerEvents($this,$this);
        $this->saveDefaultConfig();
        $this->cfg = new Config($this->getDataFolder()."config.yml",Config::YAML);
        $this->saveResource("questions.yml");
        switch ($this->cfg->get("language")){
            case "fr":
                $lang = "French";
                $msg = "Langue choisi: Francais";
                break;
            case "de":
                $lang = "German";
                $msg = "Langue selected: German";
                break;
            case "cs":
                $lang = "Czech";
                $msg = "Vybraný jazyk: Čeština";
                break;
            default:
                $lang = "English";
                $msg = "Langue selected: English";
                break;
        }
        $this->saveResource($lang.".yml");
        $this->msg = new Config($this->getDataFolder().$lang.".yml", Config::YAML);
        $this->getLogger()->info($msg);
        $this->getLogger()->info(TextFormat::DARK_GREEN."EasyQUIZ ENABLED!");
        $this->getLogger()->info(TextFormat::YELLOW."Running version 1.1.0");
        if ($this->cfg->get("auto_start")){
            $this->getServer()->getScheduler()->scheduleDelayedTask(new PostQuestionTask($this),$this->cfg->get("auto_interval")*20*60);
        }
        $this->resetAll();
    }

    public function onDisable(){
        $this->getLogger()->info(TextFormat::RED."EasyQUIZ DISABLED!");
    }

    public function onCommand(CommandSender $sd, Command $cmd, $label, array $args){
        if ($cmd->getName() == "eq"){
            if ($sd->hasPermission("eq.command")){
                if (!(isset($args[0]))){
                    if (!$this->data["running_quiz"]){
                        $this->newQuiz();
                    }
                    else {
                        $sd->sendMessage($this->getMsg("quiz_running"));
                    }
                }
                else {
                    $sd->sendMessage($this->getMsg("too_many_arguments"));
                }
            }
            else {
                $sd->sendMessage($this->getMsg("permission_eq_command"));
            }
            return true;
        }
        if ($cmd->getName() == "aw"){
            if ($sd->hasPermission("eq.answer")){
                if (!($sd instanceof ConsoleCommandSender)){
                    if ($this->data["running_quiz"]){
                        if (isset($this->data["correct_players"][$sd->getName()]) || isset($this->data["wrong_players"][$sd->getName()])){
                            $sd->sendMessage($this->getMsg("already_answered"));
                            return true;
                        }
                        if ($this->cfg->get("answer_format") === true or !(isset($args[1]))){
                            if ($this->cfg->get("answer_format") === true){
                                $typed = \implode(" ",$args);
                            }
                            else {
                                $typed = $this->data["answers"][$args[0]];
                            }
                            if (stripos($typed, "CORRECT_") !== false){
                                $sd->sendMessage($this->getMsg("answered"));
                                $this->data["correct_players"][$sd->getName()] = $sd->getName();
                            }
                            else {
                                $sd->sendMessage($this->getMsg("answered"));
                                $this->data["wrong_players"][$sd->getName()] = $sd->getName();
                            }
                        }
                        else {
                            $sd->sendMessage($this->getMsg("wrong_arguments"));
                        }
                    }
                    else {
                        $sd->sendMessage($this->getMsg("quiz_not_running"));
                    }
                }
                else {
                    $sd->sendMessage($this->getMsg("not_for_console"));
                }
            }
            else {
                $sd->sendMessage($this->getMsg("permission_eq_answer"));
            }
            return true;
        }
        return true;
    }

    public function newQuiz(){
        if ($this->data["questions"] !== []){
            $quiz_questions = array_rand($this->data["questions"],1);
            $this->getServer()->getScheduler()->scheduleDelayedTask(new QuestionTask($this),($this->cfg->get("time_for_answer")*20));
            $this->data["question"] = $quiz_questions;
            foreach ($this->getServer()->getOnlinePlayers() as $p){
                $p->sendMessage(TextFormat::GREEN.$this->getMsg("quiz_info"));
                $p->sendMessage("-----------------------");
                $p->sendMessage(TextFormat::YELLOW.$this->data["question"]);
                if ($this->cfg->get("show_answers") === true){
                    $p->sendMessage("-----------------------");
                    $p->sendMessage($this->getMsg("avaible_answers"));
                }
                $answers = $this->data["questions"][$this->data["question"]];
                $this->data["answers"] = $answers;
                foreach ($answers as $key => $value){
                    $p->sendMessage($key.") ".$value);
                }
                $p->sendMessage("-----------------------");
                if ($this->cfg->get("answer_format") === true){
                    $p->sendMessage($this->getMsg("how_to_answer"));
                }
                else {
                    $p->sendMessage($this->getMsg("how_to_answer_two"));
                }
            }
            $this->data["running_quiz"] = true;
        }
        else {
            $this->getLogger()->critical($this->getMsg("no_questions"));
            $this->getServer()->getPluginManager()->disablePlugin($this);
        }
    }

    public function endQuiz(){
        /** @var Player $player */
        foreach ($this->data["wrong_players"] as $player) {
            if ($this->cfg->get("lose_commands") !== false and !($player->hasPermission("eq.lose"))) {
                $p = $this->getServer()->getPlayerExact($player);
                if ($p instanceof Player) {
                    $p->sendMessage($this->getMsg("lose_quiz"));
                    if ($this->cfg->get("lose_commands") !== false) {
                        foreach ($this->cfg->get("lose_commands") as $com) {
                            $this->getServer()->dispatchCommand(new ConsoleCommandSender, \str_replace("%PLAYER", $player, $com));
                        }
                    }
                }
            }
        }
        $winners = \array_rand($this->data["correct_players"], $this->cfg->get("winners"));
        if (!is_array($winners)) {
            $winners = [$winners];
        }
        $this->data["winners"] = $winners;
        foreach ($this->data["winners"] as $winner) {
            $p = $this->getServer()->getPlayerExact($winner);
            if ($p instanceof Player) {
                $p->sendMessage($this->getMsg("win_quiz"));
                if ($this->cfg->get("win_commands") !== false){
                    foreach ($this->cfg->get("win_commands") as $com){
                        $this->getServer()->dispatchCommand(new ConsoleCommandSender,\str_replace("%PLAYER",$player,$com));
                    }
                }
            }
        }
        foreach ($this->data["correct_players"] as $player){
            if (isset($this->data["winers"][$player])){
                $p = $this->getServer()->getPlayerExact($player);
                if ($p instanceof Player){
                    $p->sendMessage($this->getMsg("sorry_lose"));

                }
            }
        }
        foreach ($this->getServer()->getOnlinePlayers() as $p){
            $p->sendMessage($this->getMsg("quiz_end"));
        }
        if ($this->cfg->get("show_stats")){
            $players_lost = \count($this->data["wrong_players"]) + \count($this->data["correct_players"]) - \count($this->data["winners"]);
            foreach ($this->getServer()->getOnlinePlayers() as $p){
                $p->sendMessage("-----------------");
                $p->sendMessage($this->getMsg("answers_were"));
                foreach ($this->data["answers"] as $answer){
                    if (stripos($answer,"CORRECT_") !== false){
                        $p->sendMessage(\str_replace("CORRECT_","",\str_replace("%ANSWER",$answer,\str_replace("%QUESTION",$this->data["question"],$this->getMsg("answer_was_correct")))));
                    }
                    else {
                        $p->sendMessage(TextFormat::RED.\str_replace("CORRECT_","",\str_replace("%ANSWER",$answer,\str_replace("%QUESTION",$this->data["question"],$this->getMsg("answer_was_wrong")))));
                    }
                }
                $p->sendMessage("-----------------");
                $p->sendMessage(\str_replace("%NUMBER",\count($this->data["wrong_players"]) + \count($this->data["correct_players"]),$this->getMsg("quiz_end_one")));
                if ($this->cfg->get("name_winners") === true and \count($this->data["winners"]) !== 0){
                    $name = [];
                    foreach (\array_merge($this->data["correct_players"], $this->data["wrong_players"]) as $player => $how){
                        $temp_array = [$player];
                        $name = array_merge($temp_array,$name);
                    }
                    $names = implode(" ",$name);
                    $p->sendMessage(\str_replace("%VARIABLE",$names,$this->getMsg("quiz_end_two")));
                }
                else {
                    $p->sendMessage(\str_replace("%VARIABLE",count($this->data["winners"]),$this->getMsg("quiz_end_two")));
                }
                $p->sendMessage(\str_replace("%NUMBER",$players_lost,$this->getMsg("quiz_end_three")));
                $p->sendMessage("-----------------");
            }
        }
        if ($this->cfg->get("delete_question") === true){
            $questions = new Config($this->getDataFolder()."questions.yml", Config::YAML);
            $questions->remove($this->data["question"]);
            $questions->save();
        }
        $this->resetAll();
    }

    public function resetAll(){
        $this->data = ["questions" => \yaml_parse_file($this->getDataFolder()."questions.yml"),
                        "question" => "", "answers" => [], "wrong_players" => [], "correct_players" => [],
                        "winners" => [], "running_quiz" => false];
    }

    public function getMsg($msg){
        return str_replace("&","§",$this->msg->get($msg));
    }

}


