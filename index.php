<?php
session_start();

// error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// libs
require "template_engine.php";

// globals
$session_username_key = "todo-username";
$session_password_key = "todo-password";
$session_register_notice_key = "todo-register-notice";
$get_register_key = "reg";
$profile_template_path = "new_profile.json";
$profile_extension = ".json";
$profiles_path = "profiles/";
$backup_path = "backups/";
$model = array();

// setup
if (!file_exists("backups"))
{
	mkdir("backups");
}
if (!file_exists("profiles"))
{
	mkdir("profiles");
}

// post
if (!empty($_POST["intent"]))
{
	if ($_POST["intent"] == "login" && !empty($_POST["uname"]) && !empty($_POST["pwd"]))
	{
		$username = $_POST["uname"];
		$password = $_POST["pwd"];
		
		$_SESSION[$session_username_key] = $username;
		$_SESSION[$session_password_key] = $password;
		redirect("./");
		exit(0);
	}
	else if ($_POST["intent"] == "register" && !empty($_POST["uname"]) && !empty($_POST["pwd"]) && !empty($_POST["pwd2"]))
	{
		unset($_SESSION[$session_register_notice_key]);
		
		$username = $_POST["uname"];
		$password = $_POST["pwd"];
		$password2 = $_POST["pwd2"];
		
		$valid = false;
		if (preg_match("/^[A-Za-z0-9]{3,16}$/", $username) === 1)
		{
			$valid = true;
		}
	
		$profile_file = $profiles_path . strtolower($username) . $profile_extension;
		if (!$valid)
		{
			$_SESSION[$session_register_notice_key] = "Error: Invalid username.";
			redirect("./?{$get_register_key}");
		}
		else if (file_exists($profile_file))
		{
			$_SESSION[$session_register_notice_key] = "Error: Username is already taken.";
			redirect("./?{$get_register_key}");
		}
		else if (preg_match("/.{8,265}/", $password) !== 1)
		{
			$_SESSION[$session_register_notice_key] = "Error: Invalid password.";
			redirect("./?{$get_register_key}");
		}
		else if ($password != $password2)
		{
			$_SESSION[$session_register_notice_key] = "Error: Passwords don't match.";
			redirect("./?{$get_register_key}");
		}
		else
		{
			$new_profile = json_decode(file_get_contents($profile_template_path), true);
			$new_profile["file"] = $profile_file;
			$new_profile["username"] = $username;
			$new_profile["password"] = $password;
			
			file_put_contents($profile_file, json_encode($new_profile));
			
			$_SESSION[$session_username_key] = $username;
			$_SESSION[$session_password_key] = $password;
			redirect("./");
		}
		exit(0);
	}
}

if (isset($_POST["logout"]) || isset($_GET["logout"]))
{
	unset($_SESSION[$session_username_key]);
	unset($_SESSION[$session_password_key]);
	redirect("./");
	exit(0);
}

// session
if (isset($_GET[$get_register_key]))
{
	$notice = "";
	if (!empty($_SESSION[$session_register_notice_key]))
	{
		$notice = $_SESSION[$session_register_notice_key];
	}
	
	echo register_form($notice);
	exit(0);
}
else if (!empty($_SESSION[$session_username_key]))
{
	$username = $_SESSION[$session_username_key];
	$password = "";
	if (!empty($_SESSION[$session_password_key])) $password = $_SESSION[$session_password_key];
	
	$profiles_whitelist = array();
	$glob = glob($profiles_path . "*{$profile_extension}");
	foreach ($glob as $key => $profile)
	{
		$profiles_whitelist[$key] = strtolower(pathinfo($profile)["filename"]);
	}
	
	if (!in_array(strtolower($username), $profiles_whitelist))
	{
		echo login_form("Error: username not found.");
		exit(0);
	}
	
	$index = array_search(strtolower($username), $profiles_whitelist);
	$raw_json = file_get_contents($glob[$index]);
	$json = json_decode($raw_json, true);
	
	if ($json["username"] !== $username)
	{
		echo login_form("Error: wrong username.");
		exit(0);
	}
	
	if ($json["password"] !== $password)
	{
		echo login_form("Error: wrong password.");
		exit(0);
	}
	
	if ($json["username"] === $username && $json["password"] === $password) // just to be safe, the if is here
	{
		$model = $json; 
	}
}
else
{
	echo login_form();
	exit(0);
}


// actual
if (isset($_POST["backup"]) || isset($_GET["backup"]))
{
	$time_part = "-" . time();
	$backup_filename = $backup_path . strtolower($model["username"]) . $time_part . $profile_extension;
	file_put_contents($backup_filename, json_encode($model));
	redirect("./");
	exit(0);
}
else if (!empty($_POST["restore"]) || !empty($_GET["restore"]))
{
	$timestamp = 0;
	if (!empty($_POST["restore"]))
	{
		$timestamp = $_POST["restore"];
	}
	else if (!empty($_GET["restore"]))
	{
		$timestamp = $_GET["restore"];
	}
	
	$backup_prefix = $backup_path . strtolower($model["username"]) . "-";
	$backup_filename = $backup_prefix . strval($timestamp) . $profile_extension;
	$backups = glob($backup_prefix . "*" . $profile_extension);
	if (in_array($backup_filename, $backups) && file_exists($backup_filename))
	{
		$backup_json = json_decode(file_get_contents($backup_filename), true);
		$model["list"] = $backup_json["list"];
	}
	
	$file = $model["file"];
	$raw_json = json_encode($model);
	file_put_contents($file, $raw_json);
	redirect("./");
	exit(0);
}
else if (isset($_POST["delete-restore"]) || isset($_GET["delete-restore"]))
{
	$timestamp = 0;
	if (!empty($_POST["delete-restore"]))
	{
		$timestamp = $_POST["delete-restore"];
	}
	else if (!empty($_GET["delete-restore"]))
	{
		$timestamp = $_GET["delete-restore"];
	}
	
	$backup_prefix = $backup_path . strtolower($model["username"]) . "-";
	$backup_filename = $backup_prefix . strval($timestamp) . $profile_extension;
	$backup_new_filename = $backup_path . "deleted-" . strtolower($model["username"]) . "-" . strval($timestamp) . $profile_extension;
	$backups = glob($backup_prefix . "*" . $profile_extension);
	if (in_array($backup_filename, $backups) && file_exists($backup_filename))
	{
		rename($backup_filename, $backup_new_filename);
	}
	redirect("./?restore-list");
	exit(0);
}
else if (isset($_POST["restore-list"]) || isset($_GET["restore-list"]))
{
	$backups = array();
	$backup_prefix = $backup_path . strtolower($model["username"]) . "-";
	$glob = glob($backup_prefix . "*" . $profile_extension);
	natcasesort($glob);
	$glob = array_reverse($glob);
	foreach ($glob as $backup)
	{
		$timestamp_and_ext = str_replace($backup_prefix, "", $backup);
		$timestamp = intval(str_replace($profile_extension, "", $timestamp_and_ext));
		$datestamp = date("r", $timestamp);
		$backups[] = array(
			"timestamp" => $timestamp,
			"date" => $datestamp,
			"file" => $backup,
		);
	}
	
	echo restore_form($backups);
	exit(0);
}
else if (!empty($_POST["action"]))
{
	$action = $_POST["action"];
	$modified = false;
	if ($action === "add")
	{
		if (!empty($_POST["todo_title"]))
		{
			$title = $_POST["todo_title"];
			
			$description = "";
			if (!empty($_POST["todo_description"]))
			{
				$description = $_POST["todo_description"];
			}

			$deadline = "";
			if (!empty($_POST["todo_deadline"]))
			{
				$deadline = strtotime($_POST["todo_deadline"]);
			}
			
			$category = "";
			if (!empty($_POST["todo_category"]))
			{
				$category = $_POST["todo_category"];
			}
			
			$now = time();
			
			if (empty($model["list"]))
			{
				$model["list"] = array();
			}
			
			$model["list"][] = array(
				"title" => $title,
				"description" => $description,
				"deadline" => $deadline,
				"category" => $category,
				"created" => $now,
				"modified" => $now,
			);
			
			$modified = true;
		}
	}
	else if ($action === "remove")
	{
		if (isset($_POST["todo_item_id"]))
		{
			$id = $_POST["todo_item_id"];
			
			if (isset($model["list"][$id]))
			{
				unset($model["list"][$id]);
				$model["list"] = array_values($model["list"]); // renumber
				
				$modified = true;
			}
		}
	}
	else if ($action === "delay" && !empty($_POST["delay_by"]))
	{
		$delay_by = $_POST["delay_by"];
		if (isset($_POST["todo_item_id"]))
		{
			$id = $_POST["todo_item_id"];
			
			if (isset($model["list"][$id]) && !empty($model["list"][$id]["deadline"]))
			{
				$now = time();
				
				$deadline = strtotime("@".$model["list"][$id]["deadline"]);
				$deadline_new = strtotime($delay_by, $deadline);
				$model["list"][$id]["deadline"] = $deadline_new;
				$model["list"][$id]["modified"] = $now;
				$modified = true;
			}
		}
	}
	
	if ($modified)
	{
		if (!empty($model["file"]) && file_exists($model["file"]))
		{
			$file = $model["file"];
			$raw_json = json_encode($model);
			file_put_contents($file, $raw_json);
			redirect("./");
			exit(0);
		}
		else
		{
			login_form("Internal error!");
			exit(0);
			exit(0);
			exit(0);
			exit(0);
		}
	}
}
else
{
	echo todo_list($model);
}

// renderers
function todo_list(array $model = []) : string
{
	$list = array();
	if (!empty($model["list"]))
	{
		$list = $model["list"];
	}
	
	$te = new template_engine();
	$te->set_block("TITLE", $model["username"] . "'s to-do List");
	$te->append_block_template("CONTENT", "TODO_LIST");
	$te->append_block_template("CONTENT", "ADD_FORM");
	$te->append_block_template("CONTENT", "NAVBAR");
	
	$categories = array();

	$now = time();
	$now_dayth = intval(date("z", $now));
	
	if (!empty($list))
	{
		foreach ($list as $key => $item)
		{
			if (!empty($item["category"]))
			{
				$category = strtolower($item["category"]);
				$category_name = $item["category"];
				$category_basis = "custom";
			}
			else if (!empty($item["deadline"]))
			{
				$deadline = $item["deadline"];
				
				$ymd = date("y-m-d", $deadline);
				$day = date("l", $deadline);
				$dayth = intval(date("z", $deadline));
				
				$category = $ymd;
				if ($dayth - $now_dayth == 0)
				{
					$category_name = "Today ({$day})";
				}
				else if ($dayth - $now_dayth == 1)
				{
					$category_name = "Tomorrow ({$day})";
				}
				else if ($dayth - $now_dayth == -1)
				{
					$category_name = "Yesterday ({$day})";
				}
				else if ($dayth - $now_dayth < -7)
				{
					$category_name = "{$day} ({$ymd})";
				}
				else if ($dayth - $now_dayth < 0)
				{
					$category_name = "Last " . $day;
				}
				else if ($dayth - $now_dayth < 7)
				{
					$category_name = $day;
				}
				else if ($dayth - $now_dayth < 14)
				{
					$category_name = "Next " . $day;
				}
				else
				{
					$category_name = "{$day} ({$ymd})";
				}
				
				$category_basis = "date";
			}
			else 
			{
				$category = "uncategorized";
				$category_name = "Uncategorized";
				$category_basis = "default";
			}
			
			if (empty($categories[$category]))
			{
				$categories[$category] = array(
					"title" => $category_name,
					"basis" => $category_basis,
					"list" => array(),
				);
			}
			
			$categories[$category]["list"][$key] = $item;
		}
	}
	
	
	if (!empty($categories))
	{
		$te->set_block("TODO_ITEMS", "");
	}
	$te->set_block("DATALIST_AUTOFILLS", "");
	
	ksort($categories);
	
	$titles = array();
	foreach ($categories as $category_key => $category)
	{
		$category_name = $category["title"];
		$category_basis = $category["basis"];
		$te->set_block("TODO_CATEGORY_ITEMS", "");
		
		if ($category_basis == "custom")
		{
			$te->append_argumented_block("DATALIST_AUTOFILLS", "DATALIST_AUTOFILL", [
				"DATALIST_AUTOFILL_DATA" => $category_name,
			]);
		}
		
		foreach ($category["list"] as $key => $item)
		{
			$titles[] = $item["title"];
			
			if (empty($item["description"]))
			{
				$te->set_block_template("TODO_ITEM_SUMMARY", "TODO_ITEM_SUMMARY_NODESC");
			}
			else
			{
				$te->set_block_template("TODO_ITEM_SUMMARY", "TODO_ITEM_SUMMARY_DESC");
			}
			
			$te->append_argumented_block("TODO_CATEGORY_ITEMS", "TODO_ITEM", [ 
				"TODO_ITEM_ID" => $key,
				"TODO_ITEM_TITLE" => $item["title"],
				"TODO_ITEM_DESCRIPTION" => $item["description"],
			]);
		}
		
		$te->append_argumented_block("TODO_ITEMS", "TODO_CATEGORY", [
			"TODO_CATEGORY_ID" => $category_key,
			"TODO_CATEGORY_TITLE" => $category_name,
		]);
	}
	$te->append_argumented_block("DATALISTS", "DATALIST", [
		"DATALIST_ID" => "todo_categories",
	]);
	
	$te->set_block("DATALIST_AUTOFILLS", "");
	foreach (array_unique($titles) as $title)
	{
		$te->append_argumented_block("DATALIST_AUTOFILLS", "DATALIST_AUTOFILL", [
			"DATALIST_AUTOFILL_DATA" => $title,
		]);
	}
	$te->append_argumented_block("DATALISTS", "DATALIST", [
		"DATALIST_ID" => "todo_titles",
	]);
	
	// $te->append_block("TODO_ITEMS", "<pre><details open><summary>dump</summary>".print_r($model, true)."</details></pre>");
	// $te->append_block("TODO_ITEMS", "<pre><details open><summary>dump</summary>".print_r($categories, true)."</details></pre>");
	return $te->get_html();
	exit(0);
}

function restore_form(array $backups = array()) : string
{
	$te = new template_engine();
	
	$number = count($backups);
	foreach($backups as $key => $backup)
	{
		$title = "#" . $backup["timestamp"] . ": " . $backup["date"];
		$te->append_argumented_block("RESTORE_ITEMS", "RESTORE_ITEM", [ 
			"RESTORE_ITEM_TIMESTAMP" => $backup["timestamp"],
			"RESTORE_ITEM_TITLE" => $title,
		]);
	}
	$te->set_block("TITLE", "Zovguran: To-Do");
	$te->append_block_template("CONTENT", "RESTORE_CONTAINER");
	return $te->get_html();
	exit(0);
}

function login_form(string $notice = "") : string
{
	$te = new template_engine();
	$te->set_block("TITLE", "Zovguran: To-Do");
	$te->set_block("LOGIN_NOTICE", $notice);
	$te->append_block_template("CONTENT", "LOGIN_FORM");
	return $te->get_html();
	exit(0);
}

function register_form(string $notice = "") : string
{
	$te = new template_engine();
	$te->set_block("TITLE", "Sign Up");
	$te->set_block("REGISTER_NOTICE", $notice);
	$te->append_block_template("CONTENT", "REGISTER_FORM");
	return $te->get_html();
	exit(0);
}

function redirect($path)
{
	header('Location: ' . $path);
	exit(0); // TERMINATE!
}

// function reference($model)
// {
	// $te = new template_engine();
	// $te->get_block_names();
	// $te->set_block("TITLE", $model->get_title());
	// $te->set_block("PREVIEW_WIDTH", $model->get_preview_width());
	// $te->set_block("PREVIEW_HEIGHT", $model->get_preview_height());
	// $te->set_block("PREVIEW_WIDTH_BORDER", $model->get_preview_width() + site_data::$border_size*2);
	// $te->set_block("PREVIEW_HEIGHT_BORDER", $model->get_preview_height() + site_data::$border_size*2);
	
	// foreach ($model->get_border_colors() as $info)
	// {
		// $te->append_argumented_block("EXTRA_STYLES", "BORDER_COLOR_STYLE", [ 
			// "BORDER_CLASS" => $info["id"],
			// "BORDER_COLOR" => $info["color"]
		// ]);
	// }
	
	// if ($model->get_mode() == "image" || $model->get_mode() == "collection_image")
	// {
		// $te->set_block_template("PREVIEW_MAIN_CLASS", "PREVIEW_SINGLE_CLASS");
	// }
	
	// if ($model->has_previews())
	// {
		// $te->append_block_template("CONTENT", "PREVIEW_CONTAINER");
		// foreach ($model->get_contents() as $preview)
		// {
			// if (!isset($preview["class"])) $preview["class"] = ""; // should not be here
			// $te->append_argumented_block("PREVIEWS", "PREVIEW", [ 
				// "PREVIEW_LINK" => $preview["link"],
				// "PREVIEW_CLASS" => $preview["class"],
				// "PREVIEW_IMAGE" => $preview["image"]
			// ]);
		// }
	// }
	// else
	// {
		// $te->append_block_template("CONTENT", "ITEM_CONTAINER");
		// foreach ($model->get_contents() as $item_id => $item)
		// {
			// if (isset($item["text"])) $text = $item["text"];
			// else if (isset($item["id"])) $text = $item["id"];
			// else $text = $item_id;
			
			// $te->append_argumented_block("ITEMS", "ITEM", [ 
				// "ITEM_LINK" => $item["link"],
				// "ITEM_TEXT" => $text
			// ]);
		// }
	// }
	
	// $link_categories = $model->get_navbar_links();
	// ksort($link_categories); // sort
	// foreach ($link_categories as $key => $link_category)
	// {
		// foreach ($link_category as $key2 => $link)
		// {
			// if ($link["id"] == null) $link["id"] = $key . "-" . $key2;
			
			// if ($key == "hidden") $class = "hidden";
			// else $class = "";
			
			// $te->append_argumented_block("NAVBAR_ITEMS", "NAVBAR_LINK", [ 
				// "NAVBAR_LINK_TEXT" => $link["text"],
				// "NAVBAR_LINK_LINK" => $link["link"],
				// "NAVBAR_LINK_CLASS" => $class,
				// "NAVBAR_LINK_ID" => $link["id"]
			// ]);
		// }
		// if ($key != array_key_last($link_categories))
			// $te->append_block_template("NAVBAR_ITEMS", "NAVBAR_SPACER");
	// }
	
	// echo $te->get_html();
// }