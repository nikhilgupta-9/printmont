import React, { useState } from "react";

const CheckApi = () => {
  const [url, setUrl] = useState("");
  const [response, setResponse] = useState(null);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState("");

  const handleCheck = async () => {
    if (!url) {
      setError("Please enter an API URL.");
      return;
    }

    setLoading(true);
    setError("");
    setResponse(null);

    try {
      const res = await fetch(url);
      const data = await res.json().catch(() => res);

      setResponse({
        status: res.status,
        ok: res.ok,
        data,
      });
    } catch (err) {
      setError(`Error: ${err.message}`);
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="container my-4 p-3 border rounded-3 bg-light shadow-sm">
      <h3 className="mb-3">🔍 API Response Checker</h3>
      <div className="d-flex gap-2 mb-3">
        <input
          type="text"
          placeholder="Enter API URL (e.g. https://api.example.com/data)"
          className="form-control"
          value={url}
          onChange={(e) => setUrl(e.target.value)}
        />
        <button
          className="btn btn-primary"
          onClick={handleCheck}
          disabled={loading}
        >
          {loading ? "Checking..." : "Check"}
        </button>
      </div>

      {error && <div className="alert alert-danger">{error}</div>}

      {response && (
        <div className="border rounded p-3 bg-white">
          <h5>Response:</h5>
          <p>
            <strong>Status:</strong> {response.status}{" "}
            {response.ok ? "✅ OK" : "❌ Failed"}
          </p>
          <pre
            style={{
              backgroundColor: "#f7f7f7",
              padding: "10px",
              borderRadius: "5px",
              maxHeight: "300px",
              overflow: "auto",
            }}
          >
            {typeof response.data === "string"
              ? response.data
              : JSON.stringify(response.data, null, 2)}
          </pre>
        </div>
      )}
    </div>
  );
};

export default CheckApi;
