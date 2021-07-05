var autoClose = true;

document.addEventListener("DOMContentLoaded", function(){
    // Fetch all the details element.
	const details = document.querySelectorAll("details");

	// Add the onclick listeners.
	details.forEach((targetDetail) => {
		targetDetail.addEventListener("click", () => {
			// Close all the details that are not targetDetail.
			if (autoClose) {
				details.forEach((detail) => {
					if (detail !== targetDetail) {
						detail.removeAttribute("open");
					}
				});
			}
		});
	});
});

function editNote(id, title, description, deadline, category) {
	document.getElementById("add_form_action").value = "edit";
	document.getElementById("add_form_button").value = "Edit";
	
	document.getElementById("todo_item_id").value = id.toString();
	document.getElementById("todo_title").value = title;
	document.getElementById("todo_description").value = description;
	document.getElementById("todo_deadline").value = deadline;
	document.getElementById("todo_category").value = category;
	
	setTimeout(
		function() {
			document.getElementById("addFormSummary").click();
		}, 
		5);
	
	// document.getElementById("addFormSummary").click();
}