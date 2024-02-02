import Vue from 'vue'
import logger from './logger.js'

let ActivityTabPluginView
let ActivityTabPluginInstance

/**
 * Register the comments plugins for the Activity sidebar
 */
export function registerB2sharebridge() {
	window.OCA.Activity.registerSidebarAction({
		mount: async (el, { context, fileInfo, reload }) => {
			if (!ActivityTabPluginView) {
				const { default: ActivityB2SB } = await import('./components/B2SBSidebar.vue')
				ActivityTabPluginView = Vue.extend(ActivityB2SB)
			}
			ActivityTabPluginInstance = new ActivityTabPluginView({
				parent: context,
				propsData: {
					reloadCallback: reload,
					resourceId: fileInfo.id,
				},
			})
			ActivityTabPluginInstance.$mount(el)
			logger.info('B2sharebridge mounted in Activity sidebar action', { fileInfo })
		},
		unmount: () => {
			// destroy previous instance if available
			if (ActivityTabPluginInstance) {
				ActivityTabPluginInstance.$destroy()
			}
		},
	})

	/*window.OCA.Activity.registerSidebarEntries(async ({ fileInfo, limit, offset }) => {
		const { data: comments } = await getComments({ resourceType: 'files', resourceId: fileInfo.id }, { limit, offset })
		logger.debug('Loaded comments', { fileInfo, comments })
		const { default: CommentView } = await import('./views/ActivityCommentEntry.vue')
		const CommentsViewObject = Vue.extend(CommentView)

		return comments.map((comment) => ({
			timestamp: moment(comment.props.creationDateTime).toDate().getTime(),
			mount(element, { context, reload }) {
				this._CommentsViewInstance = new CommentsViewObject({
					parent: context,
					propsData: {
						comment,
						resourceId: fileInfo.id,
						reloadCallback: reload,
					},
				})
				this._CommentsViewInstance.$mount(element)
			},
			unmount() {
				this._CommentsViewInstance.$destroy()
			},
		}))
	})*/

	window.OCA.Activity.registerSidebarFilter((activity) => activity.type !== 'b2sharebridge')
	logger.info('b2sharebridge registered for Activity sidebar action')
}