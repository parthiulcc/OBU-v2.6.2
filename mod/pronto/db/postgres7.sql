CREATE TABLE prefix_pronto (
  id SERIAL PRIMARY KEY,
  course integer NOT NULL default '0',
  name varchar(255) default NULL,
  timemodified integer NOT NULL default '0'
);

CREATE INDEX prefix_pronto_course_idx ON prefix_pronto (course);

