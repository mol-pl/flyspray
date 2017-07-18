/**
 * Functions for changing status of comments.
 * @param {jQuery} $
 */
(function($)
{
	// private variables
	var $commentsContainer = null;
	var baseUrl = '';
	var projectId = 0;

	// init
	$(function(){
		$commentsContainer = jQuery('#comments');
		// page with comments?
		if (!$commentsContainer.length) {
			return;
		}
		// get base url
		baseUrl = $commentsContainer.attr('data-base-url');
		projectId = $commentsContainer.attr('data-project-id');

		$('.comment-status-toggle', $commentsContainer).click(function(event){
			event.preventDefault();
			var commentId = this.getAttribute('data-comment-id');
			var done = true;	// status to be set
			if (parseInt(this.getAttribute('data-comment-done')) === 1) {
				done = false;
			}
			toggleStatus(baseUrl, projectId, commentId, done, function(data){
				console.log(commentId, done, data);
			});
		});
	});

	/**
	 * Toggles status of comments
	 * @param {type} baseUrl
	 * @param {type} projectId
	 * @param {type} commentId
	 * @param {Boolean} done
	 */
	function toggleStatus(baseUrl, projectId, commentId, done, onSuccess) {
		var url = baseUrl + 'javascript/callbacks/commentstatus.php';
		$.ajax({
			url: url,
			method: 'GET',
			data: {
				project_id : projectId,
				comment_id : commentId,
				done : done ? 1 : 0
			}
		})
		.done(function(data) {
			if (typeof onSuccess == 'function') {
				onSuccess(data);
			}
		});
	}

})(jQuery);