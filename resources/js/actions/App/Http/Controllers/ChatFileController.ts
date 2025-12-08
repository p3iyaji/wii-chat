import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\ChatFileController::upload
* @see app/Http/Controllers/ChatFileController.php:13
* @route '/messages/{friend}/upload'
*/
export const upload = (args: { friend: number | { id: number } } | [friend: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: upload.url(args, options),
    method: 'post',
})

upload.definition = {
    methods: ["post"],
    url: '/messages/{friend}/upload',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\ChatFileController::upload
* @see app/Http/Controllers/ChatFileController.php:13
* @route '/messages/{friend}/upload'
*/
upload.url = (args: { friend: number | { id: number } } | [friend: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { friend: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { friend: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            friend: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        friend: typeof args.friend === 'object'
        ? args.friend.id
        : args.friend,
    }

    return upload.definition.url
            .replace('{friend}', parsedArgs.friend.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\ChatFileController::upload
* @see app/Http/Controllers/ChatFileController.php:13
* @route '/messages/{friend}/upload'
*/
upload.post = (args: { friend: number | { id: number } } | [friend: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: upload.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\ChatFileController::upload
* @see app/Http/Controllers/ChatFileController.php:13
* @route '/messages/{friend}/upload'
*/
const uploadForm = (args: { friend: number | { id: number } } | [friend: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: upload.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\ChatFileController::upload
* @see app/Http/Controllers/ChatFileController.php:13
* @route '/messages/{friend}/upload'
*/
uploadForm.post = (args: { friend: number | { id: number } } | [friend: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: upload.url(args, options),
    method: 'post',
})

upload.form = uploadForm

const ChatFileController = { upload }

export default ChatFileController