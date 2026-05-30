import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import AuditLogsView from '../views/AuditLogsView.vue'
import DashboardView from '../views/DashboardView.vue'
import LocationsView from '../views/LocationsView.vue'
import LoginView from '../views/LoginView.vue'
import MonitoringView from '../views/MonitoringView.vue'
import ReportsView from '../views/ReportsView.vue'
import SensorsView from '../views/SensorsView.vue'
import UsersView from '../views/UsersView.vue'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: '/',
      redirect: '/dashboard',
    },
    {
      path: '/login',
      name: 'login',
      component: LoginView,
      meta: { public: true },
    },
    {
      path: '/dashboard',
      name: 'dashboard',
      component: DashboardView,
      meta: { roles: ['owner', 'cashier', 'admin'] },
    },
    {
      path: '/monitoring',
      name: 'monitoring',
      component: MonitoringView,
      meta: { roles: ['owner', 'cashier', 'admin'] },
    },
    {
      path: '/reports',
      name: 'reports',
      component: ReportsView,
      meta: { roles: ['owner', 'admin'] },
    },
    {
      path: '/sensors',
      name: 'sensors',
      component: SensorsView,
      meta: { roles: ['owner', 'admin'] },
    },
    {
      path: '/users',
      name: 'users',
      component: UsersView,
      meta: { roles: ['admin'] },
    },
    {
      path: '/locations',
      name: 'locations',
      component: LocationsView,
      meta: { roles: ['owner', 'admin'] },
    },
    {
      path: '/audit-logs',
      name: 'audit-logs',
      component: AuditLogsView,
      meta: { roles: ['admin'] },
    },
  ],
})

router.beforeEach((to) => {
  const auth = useAuthStore()

  if (to.meta.public) {
    return auth.isAuthenticated ? '/dashboard' : true
  }

  if (!auth.isAuthenticated) {
    return '/login'
  }

  if (to.meta.roles && !to.meta.roles.includes(auth.role)) {
    return '/dashboard'
  }

  return true
})

export default router
