"use client";

import React, { useState } from "react";
import SearchBar from "./SearchBar";
import ResultsElements from "./ResultElements";
import ResultFolder from "./ResultFolder";
import { dummyResults, getHistory } from "../_utils/apiResolveHistory";
import { SearchParams } from "./SearchBar";

export default function HistoryMain() {
    const [results, setResults] = useState<any[]>([]);
    const [loading, setLoading] = useState(false);
    const [searchParams, setSearchParams] = useState<SearchParams>();

    const fetchSearchResults = async (params: SearchParams) => {
        setLoading(true);
        setSearchParams(params);

        try {
            console.log("Fetching history with params:", params);
            const response = await getHistory(params);

            const result = params.filterSearchType.label === "Folders"
                ? response.result.folder_data
                : response.result.video_data;

            setResults(result);
        } catch (error) {
            console.error("Gagal fetch data:", error);
            setResults([]);
        }

        setLoading(false);
    };

    return (
        <>
            <SearchBar onSubmit={fetchSearchResults} />
            {searchParams?.filterSearchType.label === "Folders" ?

                <ResultFolder data={results} loading={loading} />
                :
                <ResultsElements data={results} loading={loading} />
            }

        </>
    );
}
