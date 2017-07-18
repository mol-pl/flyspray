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
	var i18n = {
		'comment-done' : '',
		'comment-done-long' : '',
		'comment-undone' : '',
		'comment-undone-long' : '',
	};

	// init
	$(function(){
		$commentsContainer = jQuery('#comments');
		// page with comments?
		if (!$commentsContainer.length) {
			return;
		}
		// get base settings
		baseUrl = $commentsContainer.attr('data-base-url');
		projectId = $commentsContainer.attr('data-project-id');

		// get i18n settings
		for (var key in i18n) {
			var value = $commentsContainer.attr('data-i18n-'+key);
			if (typeof value === 'string') {
				i18n[key] = value;
			}
		}
		//console.log('i18n: ', i18n);

		$('.comment-status-toggle', $commentsContainer).click(function(event){
			event.preventDefault();
			var commentId = this.getAttribute('data-comment-id');
			var done = true;	// status to be set
			if (parseInt(this.getAttribute('data-comment-done')) === 1) {
				done = false;
			}
			toggleStatus(baseUrl, projectId, commentId, done, function(data){
				console.log('comment status set to: ', done, commentId, data);
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
			if (data === 'OK') {
				if (typeof onSuccess == 'function') {
					onSuccess(data);
				}
			} else {
				console.warn('[toggleStatus] possible problem settings status: ', data);
			}
		})
		.fail(function(jqXHR, textStatus) {
			console.error('[toggleStatus] problem settings status: ', textStatus, jqXHR);
		});
	}

})(jQuery);