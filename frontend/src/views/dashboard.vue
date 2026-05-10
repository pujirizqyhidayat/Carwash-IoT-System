<template>
  <MainLayout>
    <div class="max-w-6xl mx-auto">
      <div class="mb-8">
        <h1 class="text-2xl font-bold text-gray-900">Dashboard Utama</h1>
        <p class="text-gray-500">Ringkasan data operasional cuci mobil hari ini.</p>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
          <p class="text-sm font-medium text-gray-500 uppercase">Kendaraan Masuk</p>
          <div class="flex items-end justify-between mt-2">
            <h3 class="text-3xl font-bold text-gray-900">42</h3>
            <span class="text-green-600 text-sm font-bold bg-green-50 px-2 py-1 rounded-lg">+12%</span>
          </div>
        </div>

        <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
          <p class="text-sm font-medium text-gray-500 uppercase">Kapasitas Saat Ini</p>
          <div class="flex items-end justify-between mt-2">
            <h3 class="text-3xl font-bold text-gray-900">8 / 15</h3>
            <div class="w-24 bg-gray-100 h-2 rounded-full overflow-hidden">
              <div class="bg-blue-500 h-full w-[53%]"></div>
            </div>
          </div>
        </div>

        <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
          <p class="text-sm font-medium text-gray-500 uppercase">Estimasi Antrian</p>
          <div class="flex items-end justify-between mt-2">
            <h3 class="text-3xl font-bold text-gray-900">~15 mnt</h3>
            <span class="text-gray-400 text-xs italic">Normal</span>
          </div>
        </div>

        <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
          <p class="text-sm font-medium text-gray-500 uppercase">Status Sensor</p>
          <div class="flex items-center gap-2 mt-4">
            <div class="w-3 h-3 bg-green-500 rounded-full animate-pulse"></div>
            <h3 class="text-lg font-semibold text-gray-700">Aktif</h3>
          </div>
        </div>
      </div>

      <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="p-6 border-b border-gray-100 flex justify-between items-center">
          <h3 class="font-bold text-gray-800">Log Kedatangan Terakhir</h3>
          <button class="text-blue-600 text-sm font-semibold hover:underline">Lihat Semua</button>
        </div>
        <table class="w-full text-left">
          <thead class="bg-gray-50 text-gray-400 text-xs uppercase font-semibold">
            <tr>
              <th class="px-6 py-4">Waktu</th>
              <th class="px-6 py-4">Status</th>
              <th class="px-6 py-4">ID Sensor</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr v-for="i in 5" :key="i" class="hover:bg-gray-50 transition-colors">
              <td class="px-6 py-4 text-sm text-gray-600">14:2{{ i }} WIB</td>
              <td class="px-6 py-4">
                <span class="px-2 py-1 bg-blue-50 text-blue-600 text-[10px] font-bold rounded uppercase">Masuk</span>
              </td>
              <td class="px-6 py-4 text-sm font-mono text-gray-500">ESP32-S001</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </MainLayout>
</template>

<script setup>
import MainLayout from '../layouts/mainlayout.vue';
</script>