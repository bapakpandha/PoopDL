import { Link } from 'react-router-dom'

export default function Topbar() {
  return (
    <nav className="bg-gray-800 text-white px-4 py-3 flex justify-between items-center">
      <div className="text-xl font-bold">📥 Video Scraper</div>
      <div className="space-x-4 hidden md:flex">
        <Link to="/">Scrape Video</Link>
        <Link to="/bulk">Bulk</Link>
        <Link to="/search">Search</Link>
        <Link to="/browse">Browse</Link>
      </div>
    </nav>
  )
}
