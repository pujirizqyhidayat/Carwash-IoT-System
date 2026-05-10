<template>
  <div class="flex h-screen bg-gray-50 font-sans text-gray-900">
    <aside class="w-64 bg-slate-900 text-white flex flex-col shadow-xl">
      <div class="p-6 text-xl font-bold tracking-wider border-b border-slate-800 flex items-center gap-3">
        <div class="w-8 h-8 bg-blue-500 rounded-lg flex items-center justify-center text-sm">IoT</div>
        CarWash Counter
      </div>
      
      <nav class="flex-1 p-4 space-y-2 mt-4">
        <router-link 
          to="/dashboard" 
          class="flex items-center gap-3 py-3 px-4 rounded-xl transition-all duration-200 hover:bg-slate-800 group"
          active-class="bg-blue-600 hover:bg-blue-600 shadow-lg shadow-blue-900/20"
        >
          <span class="text-sm font-medium">Dashboard Overview</span>
        </router-link>

        <router-link 
          to="/monitoring" 
          class="flex items-center gap-3 py-3 px-4 rounded-xl transition-all duration-200 hover:bg-slate-800"
          active-class="bg-blue-600 hover:bg-blue-600 shadow-lg shadow-blue-900/20"
        >
          <span class="text-sm font-medium">Real-time Monitoring</span>
        </router-link>

        <router-link 
          to="/reports" 
          class="flex items-center gap-3 py-3 px-4 rounded-xl transition-all duration-200 hover:bg-slate-800"
          active-class="bg-blue-600 hover:bg-blue-600 shadow-lg shadow-blue-900/20"
        >
          <span class="text-sm font-medium">Laporan Harian</span>
        </router-link>
      </nav>

      <div class="p-4 border-t border-slate-800">
        <div class="flex items-center gap-3 px-4 py-3 mb-2">
          <div class="w-10 h-10 rounded-full bg-slate-700 flex items-center justify-center font-bold text-blue-400">
            JD
          </div>
          <div class="overflow-hidden">
            <p class="text-sm font-semibold truncate">John Doe</p>
            <p class="text-xs text-slate-400">President Director</p>
          </div>
        </div>
        <button 
          @click="handleLogout" 
          class="w-full mt-2 bg-red-500/10 text-red-500 hover:bg-red-500 hover:text-white py-2.5 rounded-xl transition-all font-semibold text-sm"
        >
          Log Out
        </button>
      </div>
    </aside>

    <main class="flex-1 flex flex-col overflow-hidden">
      <header class="h-16 bg-white border-b border-gray-200 flex items-center justify-between px-8">
        <h2 class="font-semibold text-gray-700">Sistem Penghitung Kendaraan IoT</h2>
        <div class="flex items-center gap-4">
          <span class="text-xs px-2 py-1 bg-green-100 text-green-700 rounded-full font-medium">Sensor Online</span>
          <div class="text-sm text-gray-500">{{ currentTime }}</div>
        </div>
      </header>

      <section class="flex-1 overflow-y-auto p-8">
        <slot />
      </section>
    </main>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';

const router = useRouter();
const currentTime = ref('');

const updateTime = () => {
  const now = new Date();
  currentTime.value = now.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
};

onMounted(() => {
  updateTime();
  setInterval(updateTime, 60000);
});

const handleLogout = () => {
  localStorage.removeItem('auth_token');
  router.push('/login');
};
</script>