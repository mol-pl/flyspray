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
		'expand' : '',
		'collapse' : '',
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

		// toggle
		$('.comment-status-toggle', $commentsContainer).click(function(event){
			var toggler = this;

			event.preventDefault();
			var commentId = this.getAttribute('data-comment-id');
			var done = true;	// status to be set
			if (parseInt(this.getAttribute('data-comment-done')) === 1) {
				done = false;
			}
			toggleStatus(baseUrl, projectId, commentId, done, function(data){
				console.log('comment status set to: ', done, commentId, data);
				// setup toggler state
				toggler.textContent = !done ? i18n['comment-done'] : i18n['comment-undone'];
				toggler.title = !done ? i18n['comment-done-long'] : i18n['comment-undone-long'];
				toggler.setAttribute('data-comment-done', done ? 1 : 0);
				// setup comment state
				var $comment = $('.comment[data-comment-id='+commentId+']', $commentsContainer);
				if (done) {
					$comment.addClass('comment-done');
				} else {
					$comment.removeClass('comment-done');
				}
			});
		});

		// expand/collapse
		$('.collapsed-comment-toggle', $commentsContainer).click(function(event){
			var $comment = $(this.parentNode);
			var wasCollapsed = $comment.hasClass('comment-collapsed');
			$comment.toggleClass('comment-collapsed');
			this.textContent = !wasCollapsed ? i18n['expand'] : i18n['collapse'];
		});

		// expand/collapse all
		if ($('.comment-collapsed').length) {
			$('.collapsed-comments-controls').show();
			// expand all
			$('.collapsed-comments-expand').click(function(event){
				event.preventDefault();
				$('.collapsed-comment-toggle', $commentsContainer).each(function(){
					var $comment = $(this.parentNode);
					$comment.removeClass('comment-collapsed');
					this.textContent = i18n['collapse'];
				});
			});
			// collapse all
			$('.collapsed-comments-collapse').click(function(event){
				event.preventDefault();
				$('.collapsed-comment-toggle', $commentsContainer).each(function(){
					var $comment = $(this.parentNode);
					$comment.addClass('comment-collapsed');
					this.textContent = i18n['expand'];
				});
			});
		}
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