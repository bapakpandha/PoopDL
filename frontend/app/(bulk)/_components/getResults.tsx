import React from 'react';
import ResultCard from './ResultCard';

const GetResults = ({ urls }: { urls: string[], }) => {
  if (!urls || urls.length === 0) {
    return null;
  }

  return (
    <div className="space-y-2">
      {urls.map((url, index) => (
        <div
          key={index}
          className="p-2 bg-gray-100 rounded-md shadow-md"
        >
          <ResultCard urls={url} indexNum={index} />
        </div>
      ))}
    </div>
  );
};

export default GetResults;