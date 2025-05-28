"use client";

import React, { useState } from "react";
import SearchBar from "./SearchBar";
import ResultsElements from "./ResultElements";
import ResultFolder from "./ResultFolder";
import { getHistory } from "../_utils/apiResolveHistory";
import { SearchParams } from "./SearchBar";

export default function HistoryMain() {
    const [results, setResults] = useState<any[]>([]);
    const [loading, setLoading] = useState(false);
    const [searchParams, setSearchParams] = useState<SearchParams>();
    const [paginationNum, setPaginationNum] = useState(1); // Track pagination number
    const [isFetchingMore, setIsFetchingMore] = useState(false); // Track lazy loading state
    const [isFetchingMoreFinished, setIsFetchingMoreFinished] = useState(false); // Track if lazy loading has finished

    const fetchSearchResults = async (params: SearchParams, isLazyLoad = false) => {
        if (isLazyLoad) {
            setIsFetchingMore(true);
        } else {
            setLoading(true);
            setPaginationNum(1);
        }

        setSearchParams(params);

        try {
            const response = await getHistory({ ...params, pagination_num: paginationNum });

            const newResults = params.filterSearchType.label === "Folders"
                ? response.data.result.folder_data
                : response.data.result.video_data;

            if (results.length > 0 && response.status === "success" && response.data.result.video_data.length === 0 && response.data.result.folder_data.length === 0) {
                console.warn("No more data to fetch.");
                setIsFetchingMoreFinished(true);
            }

            setResults((prevResults) => (isLazyLoad ? [...prevResults, ...newResults] : newResults));
        } catch (error) {
            console.error("Gagal fetch data:", error);
            setResults([]);
        }

        if (isLazyLoad) {
            setIsFetchingMore(false);
        } else {
            setLoading(false);
        }
    };

    const handleLazyLoad = React.useCallback(() => {
        if (!loading && !isFetchingMore && results.length > 0) {
            const lastVisibleIndex = results.length - 5; // Check if the user is near the last 5 entries
            const lastElement = document.querySelector(`[data-index="${lastVisibleIndex}"]`);
            if (lastElement) {
                const rect = lastElement.getBoundingClientRect();
                if (rect.top < window.innerHeight) {
                    setPaginationNum((prev) => prev + 1); // Increment pagination number
                }
            }
        }
    }, [results, loading, isFetchingMore]);

    React.useEffect(() => {
        if (paginationNum > 1 && searchParams && !isFetchingMoreFinished) {
            fetchSearchResults(searchParams, true);
        }
    }, [paginationNum]);

    React.useEffect(() => {
        window.addEventListener("scroll", handleLazyLoad);
        return () => window.removeEventListener("scroll", handleLazyLoad);
    }, [handleLazyLoad]);

    return (
        <>
            <SearchBar onSubmit={fetchSearchResults} />
            {searchParams?.filterSearchType.label === "Folders" ?

                <ResultFolder data={results} loading={loading} />
                :
                <ResultsElements data={results} loading={loading} />
            }
            {isFetchingMore && (
                <div className="w-full flex justify-center items-center py-4">
                    <div className="animate-spin rounded-full h-6 w-6 border-t-2 border-b-2 border-teal-500" />
                    <span className="ml-2 text-gray-500">Loading more...</span>
                </div>
            )}
            {isFetchingMoreFinished && (
                <div className="w-full flex justify-center items-center py-4">
                    <span className="ml-2 text-gray-500">No More Data to Fetch</span>
                </div>
            )}
        </>
    );
}
