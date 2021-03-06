﻿-- Table: public.maprequest

-- DROP TABLE public.maprequest;

CREATE TABLE public.maprequest
(
  requestid serial NOT NULL,
  project character varying(255),
  map character varying(255),
  bbox geometry(Polygon,25832),
  counter integer DEFAULT 1,
  "user" character varying(40) NOT NULL,
  ip_address character varying(50) NOT NULL,
  date_insert timestamp without time zone NOT NULL DEFAULT now(),
  CONSTRAINT maprequest_pkey PRIMARY KEY (requestid),
  CONSTRAINT unique_request UNIQUE (project, map, bbox, "user", ip_address)
)
WITH (
  OIDS=FALSE
);
ALTER TABLE public.maprequest
  OWNER TO "postgres";

