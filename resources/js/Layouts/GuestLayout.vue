<template>
  <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-gray-100">
    <div>
      <Link href="/">
        <svg viewBox="0 0 62 65" fill="none" xmlns="http://www.w3.org/2000/svg" class="w-20 h-20 fill-current text-gray-500">
          <path d="M61.8548 14.6253C61.8778 14.7102 61.8895 14.7978 61.8897 14.8858V28.5615C61.8898 28.737 61.8434 28.9095 61.7554 29.0614C61.6675 29.2132 61.5409 29.3392 61.3887 29.4265L49.9104 36.0351V49.1337C49.9104 49.4902 49.7209 49.8192 49.4118 49.9884L25.4519 63.7445C25.1428 63.9137 24.7524 63.9137 24.4433 63.7445L0.483452 49.9884C0.174334 49.8192 -0.0151324 49.4902 -0.0151324 49.1337V14.8858C-0.0151324 14.5293 0.174334 14.2003 0.483452 14.0311L24.4433 0.274971C24.7524 0.105804 25.1428 0.105804 25.4519 0.274971L49.4118 14.0311C49.7209 14.2003 49.9104 14.5293 49.9104 14.8858V28.5615L61.3887 21.953C61.5409 21.8657 61.6675 21.7396 61.7554 21.5878C61.8434 21.436 61.8898 21.2635 61.8897 21.088V14.8858C61.8895 14.7978 61.8778 14.7102 61.8548 14.6253ZM59.893 16.1057V19.9669L55.7013 22.2632V18.4019L59.893 16.1057ZM55.7013 25.0695L59.893 22.7733V26.6344L55.7013 28.9307V25.0695ZM48.9146 19.9669V16.1057L53.1063 18.4019V22.2632L48.9146 19.9669ZM48.9146 22.7733V26.6344L53.1063 28.9307V25.0695L48.9146 22.7733Z" fill="#FF2D20"/>
        </svg>
      </Link>
    </div>

    <div class="w-full sm:max-w-md mt-6 px-6 py-4 bg-white shadow-md overflow-hidden sm:rounded-lg">
      <slot />
    </div>

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
</script>