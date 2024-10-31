=== Markdown Quiz and Survey ===
Contributors: gloomic
Donate link: https://www.gloomycorner.com/donate/
Tags: quiz, survey, exam, test, satisfication-survey, voting, questionnaire
Requires at least: 5.0.3
Tested up to: 5.4
Requires PHP: 7.2.10
Stable tag: 1.3
License: GNU General Public License v2.0 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

A super easy-to-use plugin for creating quizzess/surveys quickly from markdwon or csv files.

== Description ==
<a href="https://www.gloomycorner.com/quizandsurvey/">Quiz and Survey</a> is dedicated to create quizzes and surveys in an easy and quick way. It enables you to quickly create a quiz/survey by importing from a markdown or CSV file. In the file, you just need to specify each qustion's title, type, options and whether an option is an answer option .

For quizzes, the score will be calculated by the percentage of questions answered correctly in all questions. For example, if 8 of 10 questions are answered correctly, a user gets 80 (80%) points.

For surveys, each option of a question can be specified a value, that makes you create a satisfication survey.

= Online demo =
Check [the live demo with images and formulas](https://www.gloomycorner.com/quizandsurvey/demo/).

= How to use the plugin =
See [Quiz and Survey](https://www.gloomycorner.com/quizandsurvey/) for creating a new quiz/survey, viewing quiz/survey results, etc.

= Features =
* One-click import from a markdown or CSV file.
* Single-choice, multiple-choice and fill in the blank (Only for quizzes) quesitons (Use single choice for true/false questions).
* Support questions with images, formulas, etc.
* Satisfaction surveys.
* Allow setting anyone or only logged users to take a quiz/survey.

= More features =
* Demo markdown/CSV file download.
* Prohibit duplicate submissions for logged in users.
* Support shortcodes.
* Statistic reports.
* Pass scores for quizzes.

= Limitations =
* No timer support yet.
* For surveys, there are up to 10 options for a question. No results will be displayed for extra options.

= Troubleshooting =

**It shows 404 Error when displaying a quiz or survey in frontend.**
Log in to your WordPress Administration Screens, navigate to Settings > Permalinks. Select the default permalinks. Save. Then reselect your preferred permalinks. This will flush the rewrite rules and should solve your problem.
See more on [Custom Post Type 404 Errors](https://codex.wordpress.org/Common_WordPress_Errors#Custom_Post_Type_404_Errors).

**Import failed for "The csv file is not UTF-8 encoded"**
The imported CSV files should be UTF-8 encoded. You may use Notepad++ to convert the CSV to UTF-8:

Notepad++ > Encoding > Convert to UTF-8.

== Installation ==

1. Navigate to "Add New Plugin" page within your WordPress
2. Search for "Quiz and Survey"
3. Click "Install Now" link on the plugin and follow the prompts
4. Activate the plugin through the "Plugins" menu in WordPress

== Screenshots ==

1. New quiz from a file
2. Quiz list
3. Taking a quiz
4. Quiz results
5. Survey results
6. Markdown file for a quiz

== Changelog ==

= 1.3.1 =
**Bug fixes**
* Fix single backslash not working for latex.
* Fix default survey option value being 0 instead of 1.
* Fix lower question type not working in CSV.

= 1.3 =
** Features**
* Support creating a quiz/survey from a markdown file.
* Support fill in the blank questions for a quiz.
* Allow updating questions by importing from a file.
* Check upload file size.

**Optimization**
* Make a more proper quiz/suvery title from a file name with hyphens.

**Bug fixes**
* Fix submit data with one more option value.

== Frequently Asked Questions ==

= Can I create a quiz/survey with images, formulas to support math or science quizzes? =

Yes. But you need to upload an image first before you use it in a markdon/csv file.
To write Latex formulas in , you need to enable Latex by installing other plugins like [WP QuickLaTex](https://wordpress.org/plugins/wp-quicklatex)([Jetpack](https://wordpress.org/plugins/jetpack)'s Latex feature may lead to messy display).
See [Quiz and Survey](https://www.gloomycorner.com/quizandsurvey/#how-to-use) for more information, its exmple shows how to write an latex in a markdown/csv file.

= Can I create a quiz/survey by importting data from a markdown/csv file? =

Of course you can, and it is the only way provided to create a new quiz/survey.

= How to write a markdown/csv file for a quiz of survey? =

See <a href="https://www.gloomycorner.com/quizandsurvey/#how-to-use">how to use it</a>.

= How many quizzes/surveys can I create using the plugin? =

You can create as many quizzes and surveys as you wish. There are no limitations on that.

= Does this plugin offer demo quiz/survey CSV files? =

Yes, you can download the demo quiz and survey files to help creating your own quizzes and surveys.
