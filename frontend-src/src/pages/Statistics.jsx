import { useEffect, useState } from 'react'

export default function Statistics() {
  const [stats, setStats] = useState(null)
  const [loading, setLoading] = useState(true)

  useEffect(() => {
    const fetchStats = async () => {
      try {
        const res = await fetch('/api/statistics')
        const data = await res.json()
        if (data.status === 'success') {
          setStats(data.data)
        }
      } catch (error) {
        console.error('Gagal memuat statistik:', error)
      } finally {
        setLoading(false)
      }
    }

    fetchStats()
  }, [])

  return (
    <div className="max-w-3xl mx-auto mt-8">
      <h1 className="text-2xl font-bold mb-4">📈 Statistik Penggunaan</h1>

      {loading ? (
        <p className="text-gray-500">Memuat statistik...</p>
      ) : (
        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
          <StatCard label="Total Video Tersimpan" value={stats.total_videos} />
          <StatCard label="Hari Ini" value={stats.today} />
          <StatCard label="Minggu Ini" value={stats.this_week} />
          <StatCard label="Bulan Ini" value={stats.this_month} />
        </div>
      )}
    </div>
  )
}

function StatCard({ label, value }) {
  return (
    <div className="bg-white shadow-md rounded-xl p-6 text-center border">
      <p className="text-gray-600">{label}</p>
      <p className="text-3xl font-bold text-blue-600">{value}</p>
    </div>
  )
}
