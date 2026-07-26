export default defineNuxtRouteMiddleware(() => {
  const { isAuthenticated } = useIsAuthenticated()

  return navigateTo({
    name: isAuthenticated.value ? "home" : "login",
  }, {
    redirectCode: 302,
    replace: true,
  })
})
