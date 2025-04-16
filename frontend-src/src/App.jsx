import { BrowserRouter as Router, Routes, Route } from 'react-router-dom'
import ScrapeVideo from './pages/ScrapeVideo'
import BulkScraping from './pages/BulkScraping'
import Topbar from './components/Topbar'
import SearchHistory from './pages/SearchHistory'
import BrowseHistory from './pages/BrowseHistory'
import Statistics from './pages/Statistics'
import Export from './pages/Export'
import Settings from './pages/Settings'

function App() {
  return (
    <Router>
      <Topbar />
      <div className="p-4">
        <Routes>
          <Route path="/" element={<ScrapeVideo />} />
          <Route path="/bulk" element={<BulkScraping />} />
          <Route path="/search" element={<SearchHistory />} />
          <Route path="/browse" element={<BrowseHistory />} />
          <Route path="/stats" element={<Statistics />} />
          <Route path="/export" element={<Export />} />
          <Route path="/settings" element={<Settings />} />
          
          {/* nanti tambahkan route lainnya */}
        </Routes>
      </div>
    </Router>
  )
}

export default App

