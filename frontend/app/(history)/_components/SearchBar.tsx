"use client";
import React from "react";
import {
  ChevronDown,
  ChevronUp,
  Search as SearchLucideIcon,
} from "lucide-react";

type SearchTypeOption = {
  label: string;
  value: string;
};

type HasSummarizedOption = {
  label: string;
};

type isFetchedOption = {
  label: string;
};

type sortByOption = {
  label: string;
};

type sortTypeOption = {
  label: string;
};

export type SearchParams = {
  searchKeyword: string;
  filterSearchType: SearchTypeOption;
  filterDateScrappedStart: string;
  filterDateScrappedEnd: string;
  filterHasSummarized: HasSummarizedOption;
  filterIsFetched: isFetchedOption;
  filterSortBy: sortByOption;
  filterSortType: sortTypeOption;
};

interface SearchBarProps {
  onSubmit: (params: SearchParams) => void;
}

const optionsSearchTypes: SearchTypeOption[] = [
  { label: "Files", value: "Search Files" },
  { label: "Folders", value: "Search Folder" },
];

const optionsHasSummarized: HasSummarizedOption[] = [
  { label: "All" },
  { label: "Summarized" },
  { label: "Has Not Summarized Yet" },
];

const optionsIsFetched: isFetchedOption[] = [
  { label: "All" },
  { label: "Fetched" },
  { label: "Has Not Fetched Yet" },
];

const optionsSortBy: sortByOption[] = [
  { label: "Time Fetched" }, // For files and folders
  { label: "Name" }, // For files and folders
  { label: "Size" }, // For files only
  { label: "Length" }, // For files only
  { label: "Total Videos" }, // For folders only
];

const optionsSortType = [
  { label: "Descending" },
  { label: "Ascending" },
];

const formatDate = (date: Date) => date.toISOString().split("T")[0];

const SearchBar: React.FC<SearchBarProps> = ({ onSubmit }) => {
  const [searchKeyword, setSearchKeyword] = React.useState("");
  const [isLoading, setLoading] = React.useState(false);
  const [isShowFilter, toggleShowFilter] = React.useState(false);
  const [filterDateScrappedStart, setfilterDateScrappedStart] = React.useState(
    formatDate(new Date("2020-01-01"))
  );
  const [filterDateScrappedEnd, setfilterDateScrappedEnd] = React.useState(
    formatDate(new Date())
  );
  const [filterSearchType, setFilterSearchType] = React.useState<SearchTypeOption>(
    optionsSearchTypes[0]
  );
  const [filterHasSummarized, setFilterHasSummarized] =
    React.useState<HasSummarizedOption>(optionsHasSummarized[0]);

  const [filterIsFetched, setFilterIsFetched] =
    React.useState<isFetchedOption>(optionsIsFetched[0]);

  const [filterSortBy, setFilterSortBy] =
    React.useState<sortByOption>(optionsSortBy[0]);

  const [filterSortType, setFilterSortType] =
    React.useState(optionsSortType[0]);

  // Debounce submit
  React.useEffect(() => {
    const timeOutId = setTimeout(() => submitHandler(), 1000);
    return () => clearTimeout(timeOutId);
  }, [searchKeyword, filterSearchType, filterHasSummarized, filterSortBy, filterSortType]);

  const filterShowHandler = () => {
    toggleShowFilter(!isShowFilter);
  };

  const resetHandler = () => {
    setSearchKeyword("");
    setfilterDateScrappedStart(formatDate(new Date("2020-01-01")));
    setfilterDateScrappedEnd(formatDate(new Date()));
    setFilterSearchType(optionsSearchTypes[0]);
    setFilterHasSummarized(optionsHasSummarized[0]);
    setFilterIsFetched(optionsIsFetched[0]);
    setFilterSortBy(optionsSortBy[0]);
    setFilterSortType(optionsSortType[0]);
  };

  const submitHandler = () => {
    setLoading(true);

    const params: SearchParams = {
      searchKeyword,
      filterSearchType,
      filterDateScrappedStart,
      filterDateScrappedEnd,
      filterHasSummarized,
      filterIsFetched,
      filterSortBy,
      filterSortType,
    };

    onSubmit(params);
    setLoading(false);
  };

  return (
    <div className="w-full space-y-2 flex flex-col">
      <div className="flex flex-col items-center rounded-xl border border-gray-200 bg-white p-6 shadow-lg">
        <h1 className="text-2xl font-bold text-gray-900 dark:text-white">
          History
        </h1>
        <p className="mt-2 mb-4 text-gray-600 dark:text-gray-400">
          Search Across History
        </p>
        <form
          onSubmit={(e) => {
            e.preventDefault();
            submitHandler();
          }}
          className="w-full flex flex-col items-center"
        >
          <div className="relative mb-2 w-full flex items-center justify-between rounded-md">
            <SearchLucideIcon className="absolute left-2 block h-5 w-5 text-gray-400" />
            <input
              type="text"
              name="search"
              value={searchKeyword}
              onChange={(e) => setSearchKeyword(e.target.value)}
              className="h-12 w-full cursor-text rounded-md border border-gray-100 bg-gray-100 py-4 pr-40 pl-12 shadow-sm outline-none focus:border-teal-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
              placeholder="Search by video title, video id, etc"
            />
            <button
              type="button"
              onClick={filterShowHandler}
              className="absolute right-2 block h-5 w-5 text-gray-400"
            >
              {isShowFilter ? <ChevronUp /> : <ChevronDown />}
            </button>
          </div>

          <div
            className={
              "mt-4 w-full grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4" +
              (isShowFilter ? "" : " hidden")
            }
          >
            <div className="flex flex-col">
              <label
                htmlFor="searchType"
                className="text-sm font-medium text-stone-600"
              >
                Type
              </label>
              <select
                id="searchType"
                value={filterSearchType.label}
                onChange={(e) =>
                  setFilterSearchType(
                    optionsSearchTypes.find(
                      (opt) => opt.label === e.target.value
                    ) || optionsSearchTypes[0]
                  )
                }
                className="mt-2 block w-full rounded-md border border-gray-100 bg-gray-100 px-2 py-2 shadow-sm outline-none focus:border-teal-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50 text-gray-600"
              >
                {optionsSearchTypes.map((option, index) => (
                  <option key={index}>{option.label}</option>
                ))}
              </select>
            </div>

            <div className="flex flex-col">
              <label
                htmlFor="dateScrappedStart"
                className="text-sm font-medium text-stone-600"
              >
                Date Scrapped Start
              </label>
              <input
                type="date"
                id="dateScrappedStart"
                value={filterDateScrappedStart}
                onChange={(e) => setfilterDateScrappedStart(e.target.value)}
                className="mt-2 block w-full cursor-pointer rounded-md border border-gray-100 bg-gray-100 px-2 py-2 shadow-sm outline-none focus:border-teal-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50 text-gray-600"
              />
            </div>

            <div className="flex flex-col">
              <label
                htmlFor="dateScrappedEnd"
                className="text-sm font-medium text-stone-600"
              >
                Date Scrapped End
              </label>
              <input
                type="date"
                id="dateScrappedEnd"
                value={filterDateScrappedEnd}
                onChange={(e) => setfilterDateScrappedEnd(e.target.value)}
                className="mt-2 block w-full cursor-pointer rounded-md border border-gray-100 bg-gray-100 px-2 py-2 shadow-sm outline-none focus:border-teal-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50 text-gray-600"
              />
            </div>

            <div className="flex flex-col">
              <label
                htmlFor="hasSummarized"
                className="text-sm font-medium text-stone-600"
              >
                Summarized Thumbnail
              </label>
              <select
                id="hasSummarized"
                disabled={filterSearchType.label === "Folders"}
                value={filterHasSummarized.label}
                onChange={(e) =>
                  setFilterHasSummarized(
                    optionsHasSummarized.find(
                      (opt) => opt.label === e.target.value
                    ) || optionsHasSummarized[0]
                  )
                }
                className="text-gray-600 mt-2 block w-full cursor-pointer rounded-md border border-gray-100 bg-gray-100 px-2 py-2 shadow-sm outline-none focus:border-teal-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
              >
                {optionsHasSummarized.map((option, index) => (
                  <option key={index}>{option.label}</option>
                ))}
              </select>
            </div>

            <div className="flex flex-col">
              <label
                htmlFor="isFetched"
                className="text-sm font-medium text-stone-600"
              >
                Fetched
              </label>
              <select
                id="isFetched"
                disabled={filterSearchType.label === "Folders"}
                value={filterIsFetched.label}
                onChange={(e) =>
                  setFilterIsFetched(
                    optionsIsFetched.find(
                      (opt) => opt.label === e.target.value
                    ) || optionsIsFetched[0]
                  )
                }
                className="text-gray-600 mt-2 block w-full cursor-pointer rounded-md border border-gray-100 bg-gray-100 px-2 py-2 shadow-sm outline-none focus:border-teal-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
              >
                {optionsIsFetched.map((option, index) => (
                  <option key={index}>{option.label}</option>
                ))}
              </select>
            </div>

            <div className="flex flex-col">
              <label
                htmlFor="sortBy"
                className="text-sm font-medium text-stone-600"
              >
                Sort By
              </label>
                <select
                id="sortBy"
                value={filterSortBy.label}
                onChange={(e) =>
                  setFilterSortBy(
                  optionsSortBy.find(
                    (opt) => opt.label === e.target.value
                  ) || optionsSortBy[0]
                  )
                }
                className="text-gray-600 mt-2 block w-full cursor-pointer rounded-md border border-gray-100 bg-gray-100 px-2 py-2 shadow-sm outline-none focus:border-teal-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                >
                {optionsSortBy
                  .filter((option) => {
                  if (filterSearchType.label === "Folders") {
                    return ["Time Fetched", "Name", "Total Videos"].includes(option.label);
                  } else if (filterSearchType.label === "Files") {
                    return ["Time Fetched", "Name", "Size", "Length"].includes(option.label);
                  }
                  return true;
                  })
                  .map((option, index) => (
                  <option key={index}>{option.label}</option>
                  ))}
                </select>
            </div>

            <div className="flex flex-col">
              <label
                htmlFor="sortType"
                className="text-sm font-medium text-stone-600"
              >
                Sort Type
              </label>
              <select
                id="sortType"
                value={filterSortType.label}
                onChange={(e) =>
                  setFilterSortType(
                    optionsSortType.find(
                      (opt) => opt.label === e.target.value
                    ) || optionsSortType[0]
                  )
                }
                className="text-gray-600 mt-2 block w-full cursor-pointer rounded-md border border-gray-100 bg-gray-100 px-2 py-2 shadow-sm outline-none focus:border-teal-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
              >
                {optionsSortType.map((option, index) => (
                  <option key={index}>{option.label}</option>
                ))}
              </select>
            </div>
          </div>

          <div className="mt-6 grid w-full grid-cols-2 justify-end space-x-4 md:flex">
            <button
              type="button"
              onClick={resetHandler}
              className="rounded-lg bg-gray-200 px-8 py-2 font-medium text-gray-700 outline-none hover:opacity-80 focus:ring"
            >
              Reset
            </button>
            <button
              type="submit"
              className="rounded-lg bg-teal-500 px-8 py-2 font-medium text-white outline-none hover:opacity-80 focus:ring"
              disabled={isLoading}
              onClick={() => toggleShowFilter(false)}
            >
              {isLoading ? "Loading..." : "Search"}
            </button>
          </div>
        </form>
      </div>
    </div>
  );
};

export default SearchBar;
