<template>
  <div />
</template>

<script setup>
definePageMeta({
  layout: "empty",
  middleware: ["corporate-root"],
})

useHead({
  meta: [
    {
      name: "robots",
      content: "noindex, nofollow",
    },
  ],
})
</script>
