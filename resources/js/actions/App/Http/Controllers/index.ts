import ChatFileController from './ChatFileController'
import Settings from './Settings'

const Controllers = {
    ChatFileController: Object.assign(ChatFileController, ChatFileController),
    Settings: Object.assign(Settings, Settings),
}

export default Controllers