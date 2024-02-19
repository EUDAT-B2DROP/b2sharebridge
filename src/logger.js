import { getLoggerBuilder } from '@nextcloud/logger'

export default getLoggerBuilder().setApp('b2sharebridge').detectUser().build()
