import { useEffect, useState } from 'react'

export default function Settings() {
  const [debugMode, setDebugMode] = useState(false)
  const [itemsPerPage, setItemsPerPage] = useState(10)

  useEffect(() => {
    const debug = localStorage.getItem('debugMode') === 'true'
    const perPage = parseInt(localStorage.getItem('itemsPerPage')) || 10

    setDebugMode(debug)
    setItemsPerPage(perPage)
  }, [])

  const handleSave = () => {
    localStorage.setItem('debugMode', debugMode)
    localStorage.setItem('itemsPerPage', itemsPerPage)
    alert('Pengaturan disimpan!')
  }

  return (
    <div className="max-w-xl mx-auto mt-8">
      <h1 className="text-2xl font-bold mb-6">⚙️ Settings</h1>

      <div className="mb-4">
        <label className="flex items-center space-x-2">
          <input
            type="checkbox"
            checked={debugMode}
            onChange={(e) => setDebugMode(e.target.checked)}
            className="h-5 w-5"
          />
          <span>Aktifkan Debug Mode / Verbose Response</span>
        </label>
      </div>

      <div className="mb-4">
        <label className="block mb-1">Item per halaman (Browse History)</label>
        <input
          type="number"
          value={itemsPerPage}
          onChange={(e) => setItemsPerPage(parseInt(e.target.value))}
          className="w-full border px-3 py-2 rounded-lg"
          min={1}
        />
      </div>

      <button
        onClick={handleSave}
        className="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition"
      >
        Simpan Pengaturan
      </button>

      <div className="mt-10 text-sm text-gray-500">
        <p>📦 Video Scraper v1.0</p>
        <p>Dibuat oleh kamu ✨</p>
      </div>
    </div>
  )
}
