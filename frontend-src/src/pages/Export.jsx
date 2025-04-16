export default function Export() {
    const handleExport = (format) => {
      const link = document.createElement('a')
      link.href = `/api/export?format=${format}`
      link.download = `video_history.${format}`
      document.body.appendChild(link)
      link.click()
      document.body.removeChild(link)
    }
  
    return (
      <div className="max-w-xl mx-auto mt-8">
        <h1 className="text-2xl font-bold mb-6">📤 Export History</h1>
        <p className="text-gray-700 mb-4">
          Pilih format file untuk mengekspor hasil scraping:
        </p>
  
        <div className="flex gap-4">
          <button
            onClick={() => handleExport('csv')}
            className="bg-green-500 text-white px-4 py-2 rounded-xl hover:bg-green-600 transition"
          >
            📄 Export CSV
          </button>
  
          <button
            onClick={() => handleExport('json')}
            className="bg-blue-500 text-white px-4 py-2 rounded-xl hover:bg-blue-600 transition"
          >
            📁 Export JSON
          </button>
        </div>
      </div>
    )
  }
  