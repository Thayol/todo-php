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
$profile_template_path = "templates/new_profile.json";
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
	if ($action === "add" || $action == "edit")
	{
		if (isset($_POST["todo_item_id"]))
		{
			$id_to_edit = $_POST["todo_item_id"];
		}
		
		
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
			$created = $now;
			
			if (isset($id_to_edit) && !empty($model["list"][$id_to_edit]) && !empty($model["list"][$id_to_edit]["created"]))
			{
				$created = $model["list"][$id_to_edit]["created"];
			}
			
			if (empty($model["list"]))
			{
				$model["list"] = array();
			}
			
			$new_item = array(
				"title" => $title,
				"description" => $description,
				"deadline" => $deadline,
				"category" => $category,
				"created" => $created,
				"modified" => $now,
			);
			
			if (isset($id_to_edit) && !empty($model["list"][$id_to_edit]))
			{
				$model["list"][$id_to_edit] = $new_item;
			}
			else
			{
				$model["list"][] = $new_item;
			}
			
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
	else if ($action === "pin" && !empty($_POST["category_name"]))
	{
		if (empty($model["pinned"]))
		{
			$model["pinned"] = array();
		}
		
		$category_name = $_POST["category_name"];
		if (in_array($category_name, $model["pinned"]))
		{
			foreach ($model["pinned"] as $pin_key => $pin_name)
			{
				if ($pin_name == $category_name)
				{
					unset($model["pinned"][$pin_key]);
				}
			}
		}
		else
		{
			$model["pinned"][] = $category_name;
		}
		
		$modified = true;
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


function get_fancy_date($date) : string
{
	$now = time();
	$now_dayth = intval(date("z", $now));
	
	$ymd = date("y-m-d", $date);
	$day = date("l", $date);
	$dayth = intval(date("z", $date));
	
	$fancy_date = $ymd;
	
	if ($dayth - $now_dayth == 0)
	{
		$fancy_date = "Today ({$day})";
	}
	else if ($dayth - $now_dayth == 1)
	{
		$fancy_date = "Tomorrow ({$day})";
	}
	else if ($dayth - $now_dayth == -1)
	{
		$fancy_date = "Yesterday ({$day})";
	}
	else if ($dayth - $now_dayth < -7)
	{
		$fancy_date = "{$day} ({$ymd})";
	}
	else if ($dayth - $now_dayth < 0)
	{
		$fancy_date = "Last " . $day;
	}
	else if ($dayth - $now_dayth < 7)
	{
		$fancy_date = $day;
	}
	else if ($dayth - $now_dayth < 14)
	{
		$fancy_date = "Next " . $day;
	}
	else
	{
		$fancy_date = "{$day} ({$ymd})";
	}
	
	return $fancy_date;
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
	$te->append_block_template("CONTENT", "MAIN_LIST");
	$te->append_block_template("CONTENT", "ADD_FORM");
	$te->append_block_template("CONTENT", "NAVBAR");
	
	$pinned_categories = array();
	if (!empty($model["pinned"]))
	{
		$pinned_categories = array_merge($model["pinned"]);
	}
	
	$default_category = "Uncategorized";
	$default_category_id = "uncategorized";
	
	$categories = array( $default_category_id => [] );

	$now = time();
	$now_dayth = intval(date("z", $now));
	
	$category_autofills = array();
	
	if (!empty($list))
	{
		foreach ($list as $key => $item)
		{
			$item["id"] = $key;
			
			$date = "Unscheduled";
			if (!empty($item["deadline"]))
			{
				$date = get_fancy_date($item["deadline"]);
				$date_int = $item["deadline"];
			}
			else
			{
				$date_int = "-1";
			}
			
			if (!empty($item["category"]))
			{
				$category_name = $item["category"];
				
				$category_autofills[] = $item["category"];
			}
			else 
			{
				$category_name = $default_category;
			}
			
			$category = strtolower($category_name);
			$category = str_replace(" ", "-", $category);
			$category = str_replace([ "\t", "\n", "\r", "\0", "\v" ], "--", $category);
			
			$is_pinned = false;
			if (in_array($category, $pinned_categories))
			{
				$is_pinned = true;
			}
			
			if (empty($categories[$category]))
			{
				$categories[$category] = array(
					"title" => $category_name,
					"id" => $category,
					"list" => array(),
					"pinned" => $is_pinned,
				);
			}
			
			if (empty($categories[$category]["list"][$date_int]))
			{
				$categories[$category]["list"][$date_int] = array(
					"title" => $date,
					"id" => $date_int,
					"list" => array(),
				);
			}
			$categories[$category]["list"][$date_int]["list"][$key] = $item;
		}
	}
	
	if (!empty($categories))
	{
		$te->set_block("MAIN_ITEMS", "");
	}
	$te->set_block("DATALIST_AUTOFILLS", "");
	
	foreach ($categories as $category_key => $category)
	{
		if (!empty($categories[$category_key]["list"]))
		{
			ksort($categories[$category_key]["list"]);
		}
	}
	
	uasort($categories, function($a, $b)
	{
		global $pinned_categories;
		
		$is_a_pinned = $a["pinned"];
		$is_b_pinned = $b["pinned"];
		
		if ($is_a_pinned && $is_b_pinned)
		{
			return 0;
		}
		else if ($a["id"] == "uncategorized")
		{
			if ($is_b_pinned)
			{
				return 1;
			}
			else
			{
				return -1;
			}
		}
		else if ($is_a_pinned)
		{
			return -1;
		}
		return 1;
	});
	
	$category_autofills = array_unique($category_autofills);
	foreach ($category_autofills as $autofill)
	{
		$te->append_argumented_block("DATALIST_AUTOFILLS", "DATALIST_AUTOFILL", [
			"DATALIST_AUTOFILL_DATA" => $autofill,
		]);
	}
	
	if (!empty($json_requested) || isset($_GET["json"]))
	{
		header("Content-Type: application/json");
		echo json_encode($categories);
		exit(0);
	}
	
	$titles = array();
	foreach ($categories as $category_key => $category)
	{
		if (empty($category["list"]))
		{
			continue;
		}
		
		$te->set_block("MAIN_CATEGORY_ITEMS", "");
		
		foreach ($category["list"] as $date_key => $date)
		{
			$te->set_block("MAIN_DATE_ITEMS", "");
			
			foreach ($date["list"] as $key => $item)
			{
				$titles[] = $item["title"];
				
				$deadline = "N/A";
				$created = "N/A";
				$modified = "N/A";
				$displayed_category = "Uncategorized";
				$category_raw = $item["category"];
				
				if (empty($item["description"]))
				{
					$te->set_block_template("MAIN_ITEM_SUMMARY", "MAIN_ITEM_SUMMARY_NODESC");
				}
				else
				{
					$te->set_block_template("MAIN_ITEM_SUMMARY", "MAIN_ITEM_SUMMARY_DESC");
				}
				
				if (isset($item["deadline"]))
				{
					$deadline = date("Y-m-d", intval($item["deadline"]));
				}
				
				if (isset($item["created"]))
				{
					$created = date("Y-m-d", intval($item["created"]));
				}
				
				if (isset($item["modified"]))
				{
					$modified = date("Y-m-d", intval($item["modified"]));
				}
				
				if (!empty($item["category"]))
				{
					$displayed_category = $item["category"];
				}
				
				$te->append_argumented_block("MAIN_DATE_ITEMS", "MAIN_ITEM", [ 
					"MAIN_ITEM_ID" => $key,
					"MAIN_ITEM_TITLE" => str_replace([ "\"", "'" ], [ "&quot;", "&apos;" ], $item["title"]),
					"MAIN_ITEM_DESCRIPTION" => str_replace([ "\"", "'" ], [ "&quot;", "&apos;" ], $item["description"]),
					"MAIN_ITEM_CATEGORY" => str_replace([ "\"", "'" ], [ "&quot;", "&apos;" ], $displayed_category),
					"MAIN_ITEM_CATEGORY_RAW" => $category_raw,
					"MAIN_ITEM_DEADLINE" => $deadline,
					"MAIN_ITEM_CREATED" => $created,
					"MAIN_ITEM_MODIFIED" => $modified,
				]);
			}
			
			$te->append_argumented_block("MAIN_CATEGORY_ITEMS", "MAIN_DATE", [
			"MAIN_DATE_ID" => $date_key,
			"MAIN_DATE_TITLE" => $date["title"],
		]);
		}
		
		$te->append_argumented_block("MAIN_ITEMS", "MAIN_CATEGORY", [
			"MAIN_CATEGORY_ID" => $category_key,
			"MAIN_CATEGORY_TITLE" => $category["title"],
			"MAIN_CATEGORY_PIN_OR_UNPIN" => $category["pinned"] ? "Unpin" : "Pin",
			"MAIN_CATEGORY_EXTRA_PIN_BUTTON_CLASS" => $category["id"] == $default_category_id ? "hidden" : "",
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
	
	// $te->append_block("MAIN_ITEMS", "<pre><details open><summary>dump</summary>".print_r($model, true)."</details></pre>");
	// $te->append_block("MAIN_ITEMS", "<pre><details open><summary>dump</summary>".print_r($categories, true)."</details></pre>");
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
