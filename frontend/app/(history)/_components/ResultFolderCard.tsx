"use client"
import React from 'react'
import { Folder, ChevronRight, ChevronDown } from 'lucide-react'
import { ResultFolder as ResultFolderType } from '../_utils/apiResolveHistory'
import ResultsElements from './ResultElements'

interface ResultFolderCardProps {
    data: ResultFolderType;
    index?: number;
}

const ResultFolderCard: React.FC<ResultFolderCardProps> = ({ data, index }) => {
    const [isFolderOpen, toggleFolderOpen] = React.useState(false);
    return (
        <div data-index={index} className='w-full flex flex-col bg-gray-100 rounded-xl border border-gray-100 p-4 text-left text-gray-600 shadow-lg sm:p-8'>
            <div className="w-full mt-4 flex align-middle items-center ">
                {isFolderOpen ? (
                    <ChevronDown onClick={() => toggleFolderOpen(!isFolderOpen)} className='mr-4 w-8 h-8'></ChevronDown>
                ) : (
                    <ChevronRight onClick={() => toggleFolderOpen(!isFolderOpen)} className='mr-4 w-8 h-8'></ChevronRight>
                )}
                <Folder color="#ffa200" strokeWidth={3} absoluteStrokeWidth className='mr-4 w-8 h-8' />
                <div className="w-full text-left">
                    <div className="mb-2 flex flex-col justify-between text-gray-600 sm:flex-row">
                        <h3 className="font-medium">{data.title} ({data.total_video || 0} Video)</h3>
                        <time className="text-xs">{data.fetched_at}</time>
                    </div>
                    <p className="text-xs">{data.folder_url}</p>
                </div>
            </div>
            {isFolderOpen && (
                <div>
                    <ResultsElements data={data.data} loading={false}/>
                </div>

            )}
        </div>

    )
}

export default ResultFolderCard