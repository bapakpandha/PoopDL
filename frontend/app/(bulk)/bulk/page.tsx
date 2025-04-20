import React from 'react'
import Header from '../../__components/Header'
import Footer from '../../__components/Footer'
import FormUi from '../_components/formUi'
import type { Metadata } from 'next'
 
export const metadata: Metadata = {
  title: 'Bulk Download - PoopDL',
  keywords: ['poop', 'download', 'funny', 'joke'],
  description: 'Download your poop with PoopDL',
  authors: [{ name: 'PoopDL Team', url: 'https://poopdl.com' }],
}
 
const bulk = () => {
  return (
    <div className="relative flex min-h-screen flex-col">
    <Header />
    <main className="flex flex-1 flex-col items-center mt-2">
      <div className="flex flex-col items-center justify-center w-full max-w-4xl p-4 bg-white rounded-lg shadow-md dark:bg-gray-800">
        <h1 className="text-2xl font-bold text-gray-900 dark:text-white">Bulk Download</h1>
        <p className="mt-2 text-gray-600 dark:text-gray-400">Download multiple videos at once.</p>
        <FormUi />
      </div>
    </main>
    <Footer />
    </div>
  )
}

export default bulk