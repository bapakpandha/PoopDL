import { useState } from 'react'

export default function SearchHistory() {
  const [form, setForm] = useState({
    domain_url: '',
    video_title: '',
    decoded_src: '',
    date_start: '',
    date_end: '',
  })

  const [results, setResults] = useState([])
  const [loading, setLoading] = useState(false)
  const [error, setError] = useState(null)

  const handleChange = (e) => {
    const { name, value } = e.target
    setForm({ ...form, [name]: value })
  }

  const handleSearch = async (e) => {
    e.preventDefault()
    setLoading(true)
    setError(null)
    setResults([])

    try {
      const res = await fetch('/api/search', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(form),
      })
      const data = await res.json()
      if (data.status === 'success') {
        setResults(data.data)
      } else {
        setError(data.message || 'Gagal mencari data.')
      }
    } catch (err) {
      setError('Gagal terhubung ke server.')
    } finally {
      setLoading(false)
    }
  }

  return (
    <div className="max-w-4xl mx-auto mt-8">
      <h1 className="text-2xl font-bold mb-4">🔍 Search History</h1>

      <form onSubmit={handleSearch} className="grid grid-cols-1 md:grid-cols-2 gap-4">
        <input type="text" name="domain_url" placeholder="Domain URL" value={form.domain_url} onChange={handleChange} className="p-2 border rounded" />
        <input type="text" name="video_title" placeholder="Judul Video" value={form.video_title} onChange={handleChange} className="p-2 border rounded" />
        <input type="text" name="decoded_src" placeholder="Video Source" value={form.decoded_src} onChange={handleChange} className="p-2 border rounded" />
        <input type="date" name="date_start" value={form.date_start} onChange={handleChange} className="p-2 border rounded" />
        <input type="date" name="date_end" value={form.date_end} onChange={handleChange} className="p-2 border rounded" />
        <button type="submit" className="md:col-span-2 bg-blue-600 hover:bg-blue-700 text-white p-2 rounded">
          {loading ? 'Mencari...' : 'Cari'}
        </button>
      </form>

      {error && <p className="mt-4 text-red-500">{error}</p>}

      {results.length > 0 && (
        <div className="mt-6 space-y-4">
          {results.map((video, idx) => (
            <div key={idx} className="bg-white shadow p-4 rounded border">
              <h2 className="font-semibold">{video.video_title}</h2>
              <p><strong>Domain:</strong> {video.domain_url}</p>
              <p><strong>Source:</strong>{' '}
                <a href={video.decoded_src} target="_blank" rel="noreferrer" className="text-blue-600 underline">
                  {video.decoded_src}
                </a>
              </p>
              <p><strong>Waktu:</strong> {video.timestamp}</p>
            </div>
          ))}
        </div>
      )}
    </div>
  )
}
