EasyQUIZ v 1.1.0 by kvetinac97
===============================
An easy quiz plugin for PocketMine

Thanks for the plugin idea to @pomee4

How does it work?
 - start quiz => answer quiz => win/lose!

There are two options how start the quiz:
 - type command /eq (permission eq.command)
 - in config.yml, set "auto_start" to true
 - you can answer with "/aw <num> or /aw <answer>" (depending on config.yml "answer_format")
 
When quiz is started, plugin'll ask randomly selected answer from questions.yml
 - and it'll also write answers (configurable: config.yml "show_answers")

Players have X seconds to answer the questions (config.yml "time_for_answer")

Then will be dispatched commands (config.yml "win_commands" & "lose_commands")
for players who have won/lost (you can't lose with permission eq.lose)

There are more features to discover, it's recommended to look at config.yml and questions.yml

You can select your language by changing the two-letter value in config.yml
Currently supported: English (en), French (fr), German (de), Czech (cs)
You can translate EasyQUIZ to your own language and create pull request on Github

<h3>You can donate for support me! Donate <a href="https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=XQ5TDS9GZ38T2">>>HERE<<</a></h3> 

Added in v1.0.1:

- You can delete question when it is posted!
- Plugin will be automatically disabled if there aren't any questions!

Added in v1.1.0:

- You can disable writing answers with the questions!
- No annoying crashes when player who has played the quiz disconnects!
- New option: config.yml "answer_format": You can set the
  answer format to /aw <num>!
- Thanks for the idea of update to @doducquang
