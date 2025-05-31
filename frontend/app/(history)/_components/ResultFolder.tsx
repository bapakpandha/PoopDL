"use client"
import React from 'react'
import { ResultFolder as ResultFolderType } from '../_utils/apiResolveHistory'
import ResultFolderCard from './ResultFolderCard'

interface ResultFolderProps {
    data: ResultFolderType[];
    loading: boolean;
}

const ResultFolder: React.FC<ResultFolderProps> = ({ data, loading }) => {
    const [isResultHidden, setIsResultHidden] = React.useState(true);

    if (loading) {
        return (
            <div className="w-full flex justify-center items-center py-8">
                <div className="animate-spin rounded-full h-8 w-8 border-t-2 border-b-2 border-teal-500" />
                <span className="ml-2 text-gray-500">Loading...</span>
            </div>
        );
    }

    if (!data || data.length === 0) {
        return (
            <div className="w-full text-center text-gray-500 mt-6">
                No results found.
            </div>
        );
    }

    return (
        <div className="flex flex-col items-center w-full">
            <div className="flex flex-row items-center">
                <button
                    onClick={() => setIsResultHidden(!isResultHidden)}
                    className="mt-4 focus:outline-none text-white bg-red-700 hover:bg-red-800 focus:ring-4 focus:ring-red-300 font-medium rounded-lg text-sm px-5 py-2.5 me-2 mb-2 dark:bg-red-600 dark:hover:bg-red-700 dark:focus:ring-red-900"
                >
                    {isResultHidden ? "Show Result" : "Hide Result"}
                </button>
            </div>
            <div className={`w-full mt-6 grid grid-cols-1 gap-4 ${isResultHidden ? "hidden" : ""}`}>
                {data.map((item, index) => (
                    <ResultFolderCard data={item} index={index}/>
                ))}
            </div>
        </div>
    )
}

export default ResultFolder