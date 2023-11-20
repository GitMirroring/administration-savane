# Ad-hoc script to remove unneeded parts of MariaDB dump.
# Public domain; originally written in 2023 by Ineiev.

# Comments for evident instructions.
/^--$/ {
  N; N; N;
  /\n-- Table structure for table `.*`\n/ d;
  /\n-- Dumping data for table `.*`\n/d;
}

# Nop sequences.
/^LOCK TABLES `.*` WRITE;$/ {
  N; N; N; N;
  /\nINSERT/ b next;
  d;
  :next
}
