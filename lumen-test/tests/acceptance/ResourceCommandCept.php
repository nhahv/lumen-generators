<?php 
$I = new AcceptanceTester($scenario);

$I->wantTo('generate a RESTful resource');
$I->runShellCommand('php artisan wn:resource task_category "name;string:unique;requied;fillable descr;text:nullable;;fillable project_id;integer;required;key,fillable due;timestamp;;fillable,date" --has-many="tags,tasks" --belongs-to="project,creator:User" --migration-file=create_task_categories');

// Checking the model
$I->seeInShellOutput('TaskCategory model generated');
$I->seeFileFound('./app/TaskCategory.php');
$I->openFile('./app/TaskCategory.php');

$I->seeInThisFile('namespace App;');
$I->seeInThisFile('class TaskCategory extends Model');
$I->seeInThisFile('protected $fillable = ["name", "descr", "project_id", "due"];');
$I->seeInThisFile('protected $dates = ["due"];');
$I->seeInThisFile('public static $rules = [
		"name" => "requied",
		"project_id" => "required",
	];');
$I->seeInThisFile('
	public function tags()
	{
		return $this->hasMany("App\Tag");
	}

	public function tasks()
	{
		return $this->hasMany("App\Task");
	}

	public function project()
	{
		return $this->belongsTo("App\Project");
	}

	public function creator()
	{
		return $this->belongsTo("App\User");
	}');
$I->deleteFile('./app/TaskCategory.php');

// Checking the migration
$I->seeInShellOutput('task_categories migration generated');
$I->seeFileFound('./database/migrations/create_task_categories.php');
$I->openFile('./database/migrations/create_task_categories.php');

$I->seeInThisFile('class CreateTaskCategoriesMigration extends Migration');
$I->seeInThisFile('Schema::create(\'task_categories\', function(Blueprint $table) {
            $table->increments(\'id\');
            $table->string(\'name\')->unique();
            $table->text(\'descr\')->nullable();
            $table->integer(\'project_id\');
            $table->timestamp(\'due\');
            $table->foreign(\'project_id\')
                ->references(\'id\')
                ->on(\'projects\');
            $table->timestamps();
        });');

$I->deleteFile('./database/migrations/create_task_categories.php');

// Checking the RESTActions trait
$I->seeFileFound('./app/Http/Controllers/RESTActions.php');
$I->deleteFile('./app/Http/Controllers/RESTActions.php');

// Checking the controller
$I->seeInShellOutput('TaskCategoriesController generated');
$I->seeFileFound('./app/Http/Controllers/TaskCategoriesController.php');
$I->openFile('./app/Http/Controllers/TaskCategoriesController.php');

$I->seeInThisFile('class TaskCategoriesController extends Controller {

	const MODEL = "App\TaskCategory";

	use RESTActions;

}');

$I->deleteFile('./app/Http/Controllers/TaskCategoriesController.php');

// Checking routes
$I->openFile('./app/Http/routes.php');
$I->seeInThisFile('
$app->get(\'task-category\', \'TaskCategoriesController@all\');
$app->get(\'task-category/{id}\', \'TaskCategoriesController@get\');
$app->post(\'task-category\', \'TaskCategoriesController@add\');
$app->put(\'task-category/{id}\', \'TaskCategoriesController@put\');
$app->delete(\'task-category/{id}\', \'TaskCategoriesController@remove\');');
$I->writeToFile('./app/Http/routes.php', '<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

$app->get("/", function () use ($app) {
    return $app->welcome();
});
');
