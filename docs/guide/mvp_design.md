## MVP Designs
Before we hope straight into coding it is important to first figure out what we are trying to create and how we might want to achieve those goals. I find the best way to do some basic system design is to start with the end user MVP state and work your way from the user interactions to the API supporting it to the underlying data models.

With a URL shortener there are two immediate interactions that jump out as crucial MVP features. The ability to create a shortened url and the ability to visit a shortened url and be redirected to the correct location.

### Wireframe

{{screenshot03}}

The most basic version of this would be a one page application that you can submit a url to an input box, click submit and below it will render a shortened url. Beyond that there is no need to wireframe a redirect but we can imagine if we visit our base domain with a `/{id}` we should be redirected to that url that the user submitted. 

### API Design

There should be two real endpoints for the MVP of this url shortener. An endpoint to create a shortened url and an endpoint that looks up shortened urls are redirects users to the full url. It seems like we need a `POST /url` endpoint that takes in one parameter which is a `url`  and returns an object with the url and a shortened id for that url.

The second endpoint that redirects is just a catch-all that will look up any id and redirect a user to the correct stored url.

### Data modeling

{{screenshot04}}

The most basic data modeling we need seems quite simple! All we need to store is the url the user submits and a unique identifier for that url which is just the shortened url we are using to uniquely identify urls and figure out where to redirect them to. For the most basic of basic MVPs we are just going to use a number to uniquely identify then, When you visit tinyuri.to/1 we need be able to look up the user submitted url by `1`.  MySQL by default will give us everything we need with an auto-incrementing primary key field `id`. It will ensure this field is always unique and that it is indexed for quick lookup.  

### Documenting designs

The now that I have the most basic version of my wireframe, API, and data modeling designs thought out and drawn up I want to make sure I keep these designs readily available and can continue to work on them. I’m going to create a new folder in my repository at the root level called `docs`:
```sh
mkdir docs
cd docs
```
And then I’m going to put a second folder within that one for my designs:
```sh
mkdir designs
cd designs
```
And I’m going to flesh out some basic documentation of the designs mentioned above. Please refer to above folders to see where the designs are being stored.