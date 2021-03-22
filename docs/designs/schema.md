## MVP Data model

### url
column name | data type | details
------------|-----------|-----------------------
id          | integer   | not null, primary key
url         | string    | not null
hash        | string    | not null, unique, max 50
created_at  | timestamp | 
updated_at  | timestamp |