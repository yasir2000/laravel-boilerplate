<template>
  <div>
    <Link
      v-if="as === 'Link'"
      :href="href"
      :method="method"
      :as="as"
      class="block w-full px-4 py-2 text-left text-sm leading-5 text-gray-700 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 transition duration-150 ease-in-out"
    >
      <slot />
    </Link>

    <button
      v-else
      :type="as"
      class="block w-full px-4 py-2 text-left text-sm leading-5 text-gray-700 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 transition duration-150 ease-in-out"
      @click="$inertia.visit(href, { method })"
    >
      <slot />
    </button>
  </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3'

defineProps({
  href: String,
  method: {
    type: String,
    default: 'get',
  },
  as: {
    type: String,
    default: 'Link',
  },
})
</script>