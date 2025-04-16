import { useState } from 'react'

export default function BulkScraping() {
  const [folderUrl, setFolderUrl] = useState('')
  const [results, setResults] = useState([])
  const [loading, setLoading] = useState(false)
  const [message, setMessage] = useState('')
  const [error, setError] = useState(null)

  const handleBulkScrape = async (e) => {
    e.preventDefault()
    setLoading(true)
    setError(null)
    setResults([])
    setMessage('')

    try {
      const res = await fetch('/api/bulk', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ url: folderUrl }),
      })
      const data = await res.json()

      if (data.status === 'success') {
        setResults(data.data)
        setMessage(data.message)
      } else {
        setError(data.message || 'Terjadi kesalahan saat bulk scraping')
      }
    } catch (err) {
      setError('Gagal menyambung ke server')
    } finally {
      setLoading(false)
    }
  }

  return (
    <div className="max-w-3xl mx-auto mt-8">
      <h1 className="text-2xl font-bold mb-4">📦 Bulk Scraping</h1>

      <form onSubmit={handleBulkScrape} className="space-y-4">
        <input
          type="text"
          placeholder="Masukkan URL folder (mis: https://example.com/f/xxxx)"
          className="w-full p-2 border rounded"
          value={folderUrl}
          onChange={(e) => setFolderUrl(e.target.value)}
          required
        />
        <button
          type="submit"
          className="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700"
        >
          {loading ? 'Scraping...' : 'Scrape Folder'}
        </button>
      </form>

      {error && <p className="mt-4 text-red-500">{error}</p>}
      {message && <p className="mt-4 text-green-600">{message}</p>}

      {results.length > 0 && (
        <div className="mt-6 space-y-4">
          {results.map((video, idx) => (
            <div key={idx} className="bg-white shadow p-4 rounded border">
              <h2 className="font-semibold">{video.video_title}</h2>
              <p><strong>Domain:</strong> {video.domain_url}</p>
              <p><strong>Stream URL:</strong>{' '}
                <a href={video.decoded_src} target="_blank" rel="noreferrer" className="text-blue-600 underline">
                  {video.decoded_src}
                </a>
              </p>
            </div>
          ))}
        </div>
      )}
    </div>
  )
}
