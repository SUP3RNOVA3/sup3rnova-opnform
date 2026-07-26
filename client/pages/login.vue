<template>
  <main class="relative flex min-h-screen items-center justify-center overflow-hidden bg-[#07090d] px-6 py-12 text-white">
    <div class="absolute inset-0 corporate-grid" aria-hidden="true" />
    <div class="absolute -left-40 top-[-18rem] h-[34rem] w-[34rem] rounded-full bg-[#1946ff]/16 blur-[130px]" aria-hidden="true" />
    <div class="absolute -bottom-72 -right-40 h-[38rem] w-[38rem] rounded-full bg-[#6547ff]/12 blur-[150px]" aria-hidden="true" />

    <section class="relative z-10 w-full max-w-md">
      <div class="mb-12 flex items-center justify-center gap-4">
        <img
          src="/img/sup3rnova-mark.svg"
          alt=""
          class="h-11 w-8 invert"
        >
        <span class="text-xl font-semibold tracking-[0.28em]">SUP3RNOVA</span>
      </div>

      <div class="rounded-2xl border border-white/10 bg-white/[0.045] px-7 py-9 shadow-2xl shadow-black/40 backdrop-blur-xl sm:px-9">
        <div class="mb-8 text-center">
          <p class="text-xs font-medium uppercase tracking-[0.22em] text-white/45">
            Forms
          </p>
          <h1
            data-testid="login-page"
            class="mt-3 text-2xl font-semibold tracking-[-0.02em] text-white"
          >
            Corporate access
          </h1>
          <p class="mt-2 text-sm leading-6 text-white/55">
            Sign in with your SUP3RNOVA account.
          </p>
        </div>

        <LoginForm :invite-token="inviteToken" />
      </div>

      <p class="mt-8 text-center text-xs text-white/30">
        Authorized users only
      </p>
    </section>
  </main>
</template>

<script setup>
import LoginForm from "~/components/pages/auth/components/LoginForm.vue"

const route = useRoute()
const inviteToken = computed(() => {
  const value = route.query?.invite_token
  return typeof value === "string" ? value : null
})

definePageMeta({
  middleware: "guest",
  layout: "empty",
})

useOpnSeoMeta({
  title: "SUP3RNOVA Forms",
  description: "Secure corporate access to SUP3RNOVA Forms.",
  robots: "noindex, nofollow",
})
</script>

<style scoped>
.corporate-grid {
  background-image:
    linear-gradient(rgba(255, 255, 255, 0.025) 1px, transparent 1px),
    linear-gradient(90deg, rgba(255, 255, 255, 0.025) 1px, transparent 1px);
  background-size: 56px 56px;
  mask-image: linear-gradient(to bottom, transparent, black 28%, black 72%, transparent);
}
</style>
