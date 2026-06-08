{{-- Directory tree partial for AJAX polling --}}
@if (! empty($directoryTree))
    <x-directory-tree :nodes="$directoryTree" :current-directory="$currentDirectory" />
@else
    <p class="text-xs text-gray-400 dark:text-gray-500 px-2">No subdirectories found</p>
@endif