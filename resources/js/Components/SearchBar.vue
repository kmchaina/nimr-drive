<template>
    <div class="relative w-full">
        <!-- Search Input -->
        <div class="relative group">
            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none transition-colors duration-200">
                <MagnifyingGlassIcon class="h-5 w-5 text-[color:var(--ui-muted-2)] group-focus-within:text-indigo-500" />
            </div>
            <input
                v-model="searchQuery"
                @input="handleSearchInput"
                @keydown.enter="performSearch"
                @keydown.escape="clearSearch"
                type="text"
                placeholder="Search files..."
                class="block w-full pl-11 pr-10 py-3 border border-[color:var(--ui-border)] rounded-2xl leading-5 bg-[color:var(--ui-surface)] backdrop-blur-md text-[color:var(--ui-fg)] placeholder-[color:var(--ui-muted-2)] focus:outline-none focus:bg-[color:var(--ui-surface-strong)] focus:ring-2 focus:ring-indigo-500/30 focus:border-transparent transition-all duration-300 sm:text-sm shadow-inner"
                :class="{ 'pr-20': searchQuery }"
            />
            <!-- Clear Button -->
            <div v-if="searchQuery" class="absolute inset-y-0 right-0 flex items-center pr-3">
                <button
                    @click="clearSearch"
                    class="p-1 rounded-full text-[color:var(--ui-muted)] hover:text-[color:var(--ui-fg)] hover:bg-[color:var(--ui-hover)] focus:outline-none transition-all duration-200"
                    title="Clear search"
                >
                    <XMarkIcon class="h-4 w-4" />
                </button>
            </div>
        </div>

        <!-- Filter Chips -->
        <div v-if="searchQuery || selectedType" class="flex flex-wrap gap-2 mt-3 animate-fade-in">
            <button
                v-for="filter in filters"
                :key="filter.id"
                @click="toggleType(filter.id)"
                class="px-3 py-1.5 rounded-full text-[10px] font-black uppercase tracking-widest transition-all duration-300 border"
                :class="selectedType === filter.id 
                    ? 'bg-indigo-500 text-white border-indigo-500 shadow-lg shadow-indigo-500/20' 
                    : 'bg-[color:var(--ui-surface)] text-[color:var(--ui-muted)] border-[color:var(--ui-border)] hover:bg-[color:var(--ui-hover)]'
                "
            >
                <div class="flex items-center gap-1.5">
                    <component :is="filter.icon" class="w-3 h-3" />
                    {{ filter.label }}
                </div>
            </button>
        </div>

        <!-- Search Results Dropdown -->
        <div
            v-if="showResults && (searchResults.length > 0 || isSearching)"
            class="absolute z-50 mt-4 w-full bg-[color:var(--ui-surface-strong)] backdrop-blur-2xl shadow-2xl max-h-96 rounded-2xl py-3 text-base ring-1 ring-[color:var(--ui-border)] overflow-auto focus:outline-none sm:text-sm border border-[color:var(--ui-border)] transform transition-all duration-300 origin-top-left"
        >
            <!-- Loading State -->
            <div v-if="isSearching" class="px-4 py-3 text-sm text-[color:var(--ui-muted)] flex items-center">
                <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-blue-500 mr-3"></div>
                Searching...
            </div>

            <!-- No Results -->
            <div v-else-if="searchResults.length === 0" class="px-4 py-3 text-sm text-[color:var(--ui-muted)]">
                No files found for "{{ searchQuery }}"
            </div>

            <!-- Search Results -->
            <div v-else>
                <div class="px-4 py-2 text-xs font-semibold text-[color:var(--ui-muted)] uppercase tracking-wider bg-black/5 dark:bg-white/5">
                    {{ searchResults.length }} result{{ searchResults.length !== 1 ? 's' : '' }} found
                </div>
                
                <button
                    v-for="result in searchResults"
                    :key="result.path"
                    @click="navigateToResult(result)"
                    class="w-full px-4 py-3 text-left hover:bg-[color:var(--ui-hover)] focus:bg-[color:var(--ui-hover)] focus:outline-none border-b border-[color:var(--ui-border)] last:border-b-0 transition-colors duration-200 group"
                >
                    <div class="flex items-center">
                        <!-- File/Folder Icon -->
                        <div class="flex-shrink-0 mr-3 p-2 bg-black/5 dark:bg-white/5 rounded-lg group-hover:scale-110 transition-transform duration-300">
                            <FolderIcon v-if="result.is_directory" class="h-5 w-5 text-blue-400" />
                            <DocumentIcon v-else class="h-5 w-5 text-indigo-400" />
                        </div>
                        
                        <!-- File Info -->
                        <div class="flex-1 min-w-0">
                            <div class="text-sm font-medium text-[color:var(--ui-fg)] truncate">
                                {{ result.name }}
                            </div>
                            <div class="text-xs text-[color:var(--ui-muted)] truncate mt-0.5">
                                <span v-if="result.folder_path">{{ formatDisplayPath(result.folder_path) }}/</span>
                                <span v-if="!result.is_directory && result.size_formatted">
                                    • {{ result.size_formatted }}
                                </span>
                            </div>
                        </div>
                        
                        <!-- Navigate Icon -->
                        <div class="flex-shrink-0 ml-2 opacity-0 group-hover:opacity-100 transition-opacity">
                            <ChevronRightIcon class="h-4 w-4 text-[color:var(--ui-muted-2)]" />
                        </div>
                    </div>
                </button>
            </div>
        </div>

        <!-- Backdrop to close dropdown -->
        <div
            v-if="showResults"
            @click="closeResults"
            class="fixed inset-0 z-40"
        ></div>
    </div>
</template>

<script setup>
import { ref, watch, nextTick } from 'vue'
import { router } from '@inertiajs/vue3'
import {
    MagnifyingGlassIcon,
    XMarkIcon,
    FolderIcon,
    DocumentIcon,
    ChevronRightIcon,
    PhotoIcon,
    DocumentTextIcon,
    TableCellsIcon,
    PresentationChartBarIcon,
    ArchiveBoxIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    currentPath: {
        type: String,
        default: ''
    }
})

const emit = defineEmits(['search-performed', 'search-cleared', 'navigate-to-result'])

const searchQuery = ref('')
const searchResults = ref([])
const isSearching = ref(false)
const showResults = ref(false)
const searchTimeout = ref(null)
const selectedType = ref(null)

const filters = [
    { id: 'folder', label: 'Folders', icon: FolderIcon },
    { id: 'pdf', label: 'PDFs', icon: DocumentTextIcon },
    { id: 'image', label: 'Images', icon: PhotoIcon },
    { id: 'document', label: 'Documents', icon: DocumentIcon },
    { id: 'spreadsheet', label: 'Spreadsheets', icon: TableCellsIcon },
    { id: 'presentation', label: 'Presentations', icon: PresentationChartBarIcon },
    { id: 'archive', label: 'Archives', icon: ArchiveBoxIcon },
];

const toggleType = (typeId) => {
    if (selectedType.value === typeId) {
        selectedType.value = null;
    } else {
        selectedType.value = typeId;
    }
    performSearch();
};

// Watch for search query changes and debounce search
watch(searchQuery, (newQuery) => {
    if (searchTimeout.value) {
        clearTimeout(searchTimeout.value)
    }

    if (newQuery.trim().length === 0 && !selectedType.value) {
        clearSearch()
        return
    }

    searchTimeout.value = setTimeout(() => {
        performSearch()
    }, 300) // 300ms debounce
})

const handleSearchInput = () => {
    if (searchQuery.value.trim().length === 0 && !selectedType.value) {
        clearSearch()
    }
}

const performSearch = async () => {
    const query = searchQuery.value.trim()
    
    if (query.length < 2 && !selectedType.value) {
        return
    }

    isSearching.value = true
    showResults.value = true

    try {
        let url = `/api/search?q=${encodeURIComponent(query)}&path=${encodeURIComponent(props.currentPath)}`;
        if (selectedType.value) {
            url += `&type=${selectedType.value}`;
        }

        const response = await fetch(url, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        })

        const data = await response.json()

        if (data.success) {
            searchResults.value = data.results
            emit('search-performed', {
                query: query,
                filters: { type: selectedType.value },
                results: data.results,
                totalResults: data.total_results
            })
        } else {
            console.error('Search failed:', data.error)
            searchResults.value = []
        }
    } catch (error) {
        console.error('Search request failed:', error)
        searchResults.value = []
    } finally {
        isSearching.value = false
    }
}

const clearSearch = () => {
    searchQuery.value = ''
    selectedType.value = null
    searchResults.value = []
    showResults.value = false
    isSearching.value = false
    
    if (searchTimeout.value) {
        clearTimeout(searchTimeout.value)
    }

    emit('search-cleared')
}

const closeResults = () => {
    showResults.value = false
}

const formatDisplayPath = (path) => {
    if (!path) return '';
    if (path.startsWith('users/')) {
        const parts = path.split('/');
        // users / username / files / ...
        if (parts.length >= 3 && parts[2] === 'files') {
            const owner = parts[1];
            const subPath = parts.slice(3).join('/');
            return `Shared › ${owner}${subPath ? ' › ' + subPath : ''}`;
        }
    }
    return path;
};

const navigateToResult = (result) => {
    closeResults()
    
    // Navigate to the folder containing the file/folder
    const targetPath = result.folder_path || ''
    
    emit('navigate-to-result', {
        result: result,
        targetPath: targetPath
    })

    // Navigate using Inertia
    router.get('/dashboard', { path: targetPath }, {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => {
            // Optionally highlight the found item
            nextTick(() => {
                const element = document.querySelector(`[data-file-name="${result.name}"]`)
                if (element) {
                    element.scrollIntoView({ behavior: 'smooth', block: 'center' })
                    element.classList.add('ring-2', 'ring-blue-500', 'bg-blue-500/20')
                    setTimeout(() => {
                        element.classList.remove('ring-2', 'ring-blue-500', 'bg-blue-500/20')
                    }, 2000)
                }
            })
        }
    })
}

// Expose methods for parent component
defineExpose({
    clearSearch,
    performSearch
})
</script>

<style scoped>
/* Custom scrollbar for results dropdown */
.overflow-auto::-webkit-scrollbar {
    width: 6px;
}

.overflow-auto::-webkit-scrollbar-track {
    background: rgba(255, 255, 255, 0.05);
    border-radius: 3px;
}

.overflow-auto::-webkit-scrollbar-thumb {
    background: rgba(255, 255, 255, 0.1);
    border-radius: 3px;
}

.overflow-auto::-webkit-scrollbar-thumb:hover {
    background: rgba(255, 255, 255, 0.2);
}
</style>