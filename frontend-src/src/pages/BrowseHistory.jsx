import { useEffect, useRef, useState } from 'react'

export default function BrowseHistory() {
  const [histories, setHistories] = useState([])
  const [page, setPage] = useState(1)
  const [loading, setLoading] = useState(false)
  const [hasMore, setHasMore] = useState(true)

  const loaderRef = useRef(null)

  const fetchHistory = async () => {
    if (loading || !hasMore) return
    setLoading(true)

    try {
      const res = await fetch('/api/history', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ page, per_page: 10 }),
      })
      const data = await res.json()

      if (data.status === 'success') {
        const newItems = data.data
        setHistories(prev => [...prev, ...newItems])
        setHasMore(newItems.length === 10)
        setPage(prev => prev + 1)
      }
    } catch (err) {
      console.error('Gagal mengambil data:', err)
    } finally {
      setLoading(false)
    }
  }

  useEffect(() => {
    const observer = new IntersectionObserver(entries => {
      if (entries[0].isIntersecting && hasMore) {
        fetchHistory()
      }
    }, { threshold: 1.0 })

    if (loaderRef.current) {
      observer.observe(loaderRef.current)
    }

    return () => {
      if (loaderRef.current) {
        observer.unobserve(loaderRef.current)
      }
    }
  }, [loaderRef.current, hasMore])

  return (
    <div className="max-w-4xl mx-auto mt-8">
      <h1 className="text-2xl font-bold mb-4">📜 Browse History</h1>

      <div className="space-y-4">
        {histories.map((video, idx) => (
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

        {loading && <p className="text-center text-gray-500">Memuat...</p>}
        {!hasMore && <p className="text-center text-gray-400">Semua data telah dimuat.</p>}

        <div ref={loaderRef} className="h-10" />
      </div>
    </div>
  )
}
