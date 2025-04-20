import React from 'react'
import Header from '../../__components/Header'
import Footer from '../../__components/Footer'

const bulk = () => {
  return (
    <div className="relative flex min-h-screen flex-col">
    <Header />
    <main className="flex flex-1 flex-col items-center justify-center p-24">
      <h1 className="text-4xl font-bold">Bulk</h1>
      <p className="mt-4 text-lg">This is the bulk page.</p>
    </main>
    <Footer />
    </div>
  )
}

export default bulk