<template>
  <div class="min-h-screen bg-gray-100" :class="currentLanguage === 'ar' ? 'rtl' : 'ltr'">
    <!-- Navigation -->
    <nav class="bg-white border-b border-gray-100">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16" :class="currentLanguage === 'ar' ? 'flex-row-reverse' : 'flex-row'">
          <div class="flex">
            <!-- Logo -->
            <div class="shrink-0 flex items-center">
              <Link href="/" class="text-xl font-bold text-gray-800">
                {{ $page.props.app.name }}
              </Link>
            </div>

            <!-- Navigation Links -->
            <div class="hidden space-x-8 sm:-my-px sm:ml-10 sm:flex">
              <Link 
                href="/dashboard" 
                class="inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium"
                :class="$page.url.startsWith('/dashboard') 
                  ? 'border-indigo-400 text-gray-900' 
                  : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
              >
                Dashboard
              </Link>
            </div>
          </div>

          <!-- Settings Dropdown -->
          <div class="hidden sm:flex sm:items-center sm:ml-6" v-if="$page.props.auth.user">
            
            <!-- Language Toggle -->
            <div class="mr-3">
              <button 
                @click="toggleLanguage" 
                class="inline-flex items-center px-3 py-2 border border-gray-300 rounded-md text-sm leading-4 font-medium text-gray-500 bg-white hover:text-gray-700 focus:outline-none transition ease-in-out duration-150"
                :title="currentLanguage === 'en' ? 'Switch to Arabic' : 'Switch to English'"
              >
                <span class="mr-2">{{ currentLanguage === 'en' ? '🇺🇸' : '🇸🇦' }}</span>
                {{ currentLanguage === 'en' ? 'EN' : 'AR' }}
              </button>
            </div>

            <div class="ml-3 relative">
              <Dropdown align="right" width="48">
                <template #trigger>
                  <span class="inline-flex rounded-md">
                    <button
                      type="button"
                      class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 bg-white hover:text-gray-700 focus:outline-none transition ease-in-out duration-150"
                    >
                      {{ $page.props.auth.user.name }}
                      <svg class="ml-2 -mr-0.5 h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                      </svg>
                    </button>
                  </span>
                </template>

                <template #content>
                  <DropdownLink href="/profile">Profile</DropdownLink>
                  <DropdownLink href="/logout" method="post" as="button">
                    Log Out
                  </DropdownLink>
                </template>
              </Dropdown>
            </div>
          </div>

          <!-- Auth Links for guests -->
          <div class="hidden sm:flex sm:items-center sm:ml-6" v-else>
            <Link href="/login" class="text-gray-500 hover:text-gray-700 mr-4">Login</Link>
            <Link href="/register" class="text-gray-500 hover:text-gray-700">Register</Link>
          </div>
        </div>
      </div>
    </nav>

    <!-- Page Heading -->
    <header class="bg-white shadow" v-if="$slots.header">
      <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
        <slot name="header" />
      </div>
    </header>

    <!-- Page Content -->
    <main>
      <slot />
    </main>

    <!-- Flash Messages -->
    <div v-if="$page.props.flash.message" class="fixed top-4 right-4 max-w-sm w-full bg-blue-500 text-white p-4 rounded-md shadow-lg z-50">
      {{ $page.props.flash.message }}
    </div>
    <div v-if="$page.props.flash.error" class="fixed top-4 right-4 max-w-sm w-full bg-red-500 text-white p-4 rounded-md shadow-lg z-50">
      {{ $page.props.flash.error }}
    </div>
    <div v-if="$page.props.flash.success" class="fixed top-4 right-4 max-w-sm w-full bg-green-500 text-white p-4 rounded-md shadow-lg z-50">
      {{ $page.props.flash.success }}
    </div>
  </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3'
import Dropdown from '@/Components/Dropdown.vue'
import DropdownLink from '@/Components/DropdownLink.vue'
import { ref, onMounted, watch } from 'vue'

// Language state
const currentLanguage = ref('en')

// Load saved language preference
onMounted(() => {
  const savedLang = localStorage.getItem('hr-app-language') || 'en'
  currentLanguage.value = savedLang
  applyLanguageDirection(savedLang)
})

// Toggle language between English and Arabic
const toggleLanguage = () => {
  const newLang = currentLanguage.value === 'en' ? 'ar' : 'en'
  currentLanguage.value = newLang
  localStorage.setItem('hr-app-language', newLang)
  applyLanguageDirection(newLang)
}

// Apply RTL/LTR direction
const applyLanguageDirection = (lang) => {
  const htmlEl = document.documentElement
  if (lang === 'ar') {
    htmlEl.dir = 'rtl'
    htmlEl.lang = 'ar'
    // Add RTL classes to body for Tailwind
    document.body.classList.add('rtl')
    document.body.classList.remove('ltr')
  } else {
    htmlEl.dir = 'ltr'
    htmlEl.lang = 'en'
    document.body.classList.add('ltr')
    document.body.classList.remove('rtl')
  }
}

// Watch for language changes
watch(currentLanguage, (newLang) => {
  applyLanguageDirection(newLang)
})
</script>