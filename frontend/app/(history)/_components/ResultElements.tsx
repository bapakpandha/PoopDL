"use client";
import React from "react";
import { ResultItem } from "../_utils/apiResolveHistory";
import ResultCard from "./ResultCard";

interface ResultsElementsProps {
    data: ResultItem[];
    loading: boolean;
}

const ResultsElements: React.FC<ResultsElementsProps> = ({ data, loading }) => {
    const [isResultHidden, setIsResultHidden] = React.useState(true);
    const [isShowSummarizedThumbnail, toggleThumbnailType] = React.useState(false);
    const [isInitialThumbnailShow, toggleThumbnailShow] = React.useState(false);

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
            <div className={`flex flex-row items-center`}>
                <button
                    onClick={() => setIsResultHidden(!isResultHidden)}
                    className="mt-4 focus:outline-none text-white bg-red-700 hover:bg-red-800 focus:ring-4 focus:ring-red-300 font-medium rounded-lg text-sm px-5 py-2.5 me-2 mb-2 dark:bg-red-600 dark:hover:bg-red-700 dark:focus:ring-red-900"
                >
                    {isResultHidden ? "Show Result" : "Hide Result"}
                </button>
                <button
                    onClick={() => toggleThumbnailType(!isShowSummarizedThumbnail)}
                    className="mt-4 focus:outline-none text-white bg-teal-600 hover:bg-teal-800 focus:ring-4 focus:ring-teal-300 font-medium rounded-lg text-sm px-5 py-2.5 me-2 mb-2 dark:bg-teal-600 dark:hover:bg-teal-700 dark:focus:ring-teal-900"
                >
                    {isShowSummarizedThumbnail ? "Toggle Thumbnail" : "Toggle Summarized"}
                </button>
                <button
                    onClick={() => toggleThumbnailShow(!isInitialThumbnailShow)}
                    className="mt-4 focus:outline-none text-white bg-teal-600 hover:bg-teal-800 focus:ring-4 focus:ring-teal-300 font-medium rounded-lg text-sm px-5 py-2.5 me-2 mb-2 dark:bg-teal-600 dark:hover:bg-teal-700 dark:focus:ring-teal-900"
                >
                    {isInitialThumbnailShow ? "Toggle Show Thumbnail" : "Toggle Hide Thumbnail"}
                </button>
            </div>
            <div className={`w-full mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 ${isResultHidden ? "hidden" : ""}`}>
                {data.map((item, index) => (
                    <ResultCard data={item} isInitialSummaryShow={isShowSummarizedThumbnail} isInitialThumbnailHidden={isInitialThumbnailShow} />
                ))}
            </div>
        </div>
    );
};

export default ResultsElements;
